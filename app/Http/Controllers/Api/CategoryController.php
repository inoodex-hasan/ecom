<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Categories tree with products count.
     */
    public function index(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->withCount(['products' => fn ($p) => $p->where('status', 'published')]);
            }])
            ->withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Single category details with subcategories.
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::where('is_active', true)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug);
                if (is_numeric($slug)) {
                    $q->orWhere('id', (int) $slug);
                }
            })
            ->with(['children' => fn ($q) => $q->where('is_active', true), 'parent'])
            ->withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->first();

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }
}
