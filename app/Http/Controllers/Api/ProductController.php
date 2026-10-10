<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FlashSaleItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Paginated and filterable product catalog for Next.js /shop.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::where('status', 'published')
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'variants:id,product_id,sku,price,option_values',
            ])
            ->withCount('variants');

        // Search
        if ($request->filled('search')) {
            $s = trim($request->query('search'));
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        // Category filter (slug or id) - includes root category and any child subcategories
        if ($request->filled('category')) {
            $cat = $request->query('category');
            $categoryModel = is_numeric($cat)
                ? Category::find((int) $cat)
                : Category::where('slug', $cat)->first();

            if ($categoryModel) {
                $categoryIds = $categoryModel->getAllDescendantIds();
                $query->whereIn('category_id', $categoryIds);
            } else {
                $query->whereHas('category', function ($q) use ($cat) {
                    if (is_numeric($cat)) {
                        $q->where('id', (int) $cat);
                    } else {
                        $q->where('slug', $cat);
                    }
                });
            }
        }

        // Brand filter (slug or id)
        if ($request->filled('brand')) {
            $brand = $request->query('brand');
            $query->whereHas('brand', function ($q) use ($brand) {
                if (is_numeric($brand)) {
                    $q->where('id', (int) $brand);
                } else {
                    $q->where('slug', $brand);
                }
            });
        }

        // Price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->query('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->query('max_price'));
        }

        // Badges
        if ($request->query('badge') === 'hot') {
            $query->where('is_hot', true);
        } elseif ($request->query('badge') === 'trending') {
            $query->where('is_trending', true);
        } elseif ($request->query('badge') === 'new_arrival') {
            $query->where('is_new_arrival', true);
        }

        // In-stock only
        if ($request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        // Sorting
        $sort = $request->query('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            'name_asc' => $query->orderBy('name', 'asc'),
            default => $query->latest(),
        };

        $perPage = min(100, max(1, (int) $request->query('per_page', 16)));
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Single product details with gallery, variants, active flash deal, and reviews.
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::where('status', 'published')
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug);
                if (is_numeric($slug)) {
                    $q->orWhere('id', (int) $slug);
                }
            })
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'images',
                'variants',
                'reviews' => function ($q) {
                    $q->where('status', 'approved')->latest()->take(5);
                },
            ])
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // Check active campaign membership
        $now = now();
        $activeFlashItem = FlashSaleItem::where('product_id', $product->id)
            ->whereHas('flashSale', function ($q) use ($now) {
                $q->where('is_active', true)
                    ->where('starts_at', '<=', $now)
                    ->where('ends_at', '>=', $now);
            })
            ->with('flashSale:id,title,ends_at')
            ->first();

        $activeFlashDeal = null;
        if ($activeFlashItem && $activeFlashItem->flashSale) {
            $activeFlashDeal = [
                'campaign_id' => $activeFlashItem->flashSale->id,
                'campaign_title' => $activeFlashItem->flashSale->title,
                'flash_price' => (float) $activeFlashItem->flash_price,
                'discount_percentage' => (float) $activeFlashItem->discount_percentage,
                'ends_at' => $activeFlashItem->flashSale->ends_at,
                'quantity_limit' => $activeFlashItem->quantity_limit,
                'sold_count' => $activeFlashItem->sold_count,
            ];
        }

        // Related products in same category
        $relatedProducts = Product::where('status', 'published')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get(['id', 'name', 'slug', 'price', 'compare_price', 'primary_image', 'rating_avg', 'rating_count', 'badge_label']);

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'flash_deal' => $activeFlashDeal,
                'related_products' => $relatedProducts,
            ],
        ]);
    }
}
