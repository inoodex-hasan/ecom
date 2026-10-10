<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\InitController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API Routes for Next.js Storefront & Mobile Clients
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Health / Ping
    Route::get('/ping', fn () => response()->json(['status' => 'ok', 'app' => config('app.name'), 'time' => now()]));

    // Customer Authentication & Member Area
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:30,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:30,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/auth/orders', [AuthController::class, 'orders']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Customer Cloud Wishlist
        Route::get('/wishlist', [AuthController::class, 'getWishlist']);
        Route::post('/wishlist/toggle', [AuthController::class, 'toggleWishlist']);
        Route::post('/wishlist/sync', [AuthController::class, 'syncWishlist']);
    });

    // Store Settings & Home Aggregate Feed
    Route::get('/settings', [InitController::class, 'settings']);
    Route::get('/home', [InitController::class, 'homeFeed']);

    // Catalog & Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);

    // Promotional Campaigns & Flash Deals
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/{slug}', [CampaignController::class, 'show']);

    // Coupons Validation
    Route::post('/coupons/validate', [CouponController::class, 'validateCode'])->middleware('throttle:60,1');

    // Orders & Tracking
    Route::post('/orders', [OrderController::class, 'checkout'])->middleware('throttle:30,1');
    Route::get('/orders/track/{orderNumber}', [OrderController::class, 'track'])->middleware('throttle:60,1');

    // Customer Reviews
    Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);
    Route::post('/products/{id}/reviews', [ReviewController::class, 'store'])->middleware('throttle:20,1');

    // Content: CMS Pages, Blogs, and Contact
    Route::get('/pages', [ContentController::class, 'pages']);
    Route::get('/pages/{slug}', [ContentController::class, 'page']);
    Route::get('/blogs', [ContentController::class, 'blogs']);
    Route::get('/blogs/{slug}', [ContentController::class, 'blogDetail']);
    Route::post('/contact', [ContentController::class, 'submitContact'])->middleware('throttle:15,1');
});

// Direct top-level shortcuts for Next.js convenience
Route::get('/settings', [InitController::class, 'settings']);
Route::get('/home', [InitController::class, 'homeFeed']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/campaigns', [CampaignController::class, 'index']);
Route::post('/coupons/validate', [CouponController::class, 'validateCode']);
Route::post('/orders', [OrderController::class, 'checkout']);
Route::get('/orders/track/{orderNumber}', [OrderController::class, 'track']);
Route::get('/pages/{slug}', [ContentController::class, 'page']);
Route::get('/blogs', [ContentController::class, 'blogs']);
Route::get('/blogs/{slug}', [ContentController::class, 'blogDetail']);
Route::post('/contact', [ContentController::class, 'submitContact']);
