<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function ($response, Throwable $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $response;
            }

            $statusCode = $response->getStatusCode();

            if ($statusCode === 419) {
                return back()->with([
                    'error' => 'Your security session has expired. Please try again.',
                ]);
            }

            if (in_array($statusCode, [403, 404, 500, 503])) {
                $rawMessage = $exception->getMessage();
                $isTechnical = empty($rawMessage)
                    || str_contains($rawMessage, 'No query results for model')
                    || str_contains($rawMessage, 'App\\Models')
                    || str_contains($rawMessage, 'SQLSTATE')
                    || str_contains($rawMessage, 'Call to undefined')
                    || (str_contains($rawMessage, 'The route') && str_contains($rawMessage, 'could not be found'));

                $userFriendlyMessage = match ($statusCode) {
                    404 => 'The requested record or page could not be found.',
                    403 => 'You do not have permission to access this section.',
                    500 => 'An internal server error occurred. Please try again.',
                    503 => 'The service is temporarily unavailable due to maintenance.',
                    default => null,
                };

                return Inertia::render('Error', [
                    'status' => $statusCode,
                    'message' => $isTechnical ? $userFriendlyMessage : $rawMessage,
                ])->toResponse($request)->setStatusCode($statusCode);
            }

            return $response;
        });
    })->create();
