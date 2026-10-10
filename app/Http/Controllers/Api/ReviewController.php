<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * List approved reviews for a product with ratings breakdown.
     */
    public function index(int $productId): JsonResponse
    {
        $product = Product::find($productId);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $reviews = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->latest()
            ->paginate(10);

        // Calculate distribution
        $distribution = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
                'average_rating' => (float) $product->rating_avg,
                'reviews_count' => (int) $product->rating_count,
                'distribution' => [
                    5 => $distribution[5] ?? 0,
                    4 => $distribution[4] ?? 0,
                    3 => $distribution[3] ?? 0,
                    2 => $distribution[2] ?? 0,
                    1 => $distribution[1] ?? 0,
                ],
            ],
        ]);
    }

    /**
     * Submit a customer product review.
     */
    public function store(Request $request, int $productId): JsonResponse
    {
        $product = Product::find($productId);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:150',
            'comment' => 'required|string|max:2000',
            'reviewer_name' => 'required|string|max:100',
            'reviewer_email' => 'required|email|max:150',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'string|max:2048',
        ]);

        $autoApprove = (bool) Setting::get('reviews.auto_approve', true);
        $status = $autoApprove ? 'approved' : 'pending';

        $review = Review::create([
            'product_id' => $product->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
            'reviewer_name' => $validated['reviewer_name'],
            'reviewer_email' => $validated['reviewer_email'],
            'photos' => $validated['photos'] ?? null,
            'status' => $status,
            'is_verified_purchase' => false,
        ]);

        if ($status === 'approved') {
            $product->updateRatingMetrics();
        }

        return response()->json([
            'success' => true,
            'message' => $autoApprove
                ? 'Thank you! Your review has been published.'
                : 'Thank you! Your review has been submitted and is pending moderation.',
            'data' => $review,
        ], 201);
    }
}
