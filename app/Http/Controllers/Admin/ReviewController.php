<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Review::with([
            'product:id,name,sku,primary_image,price,rating_avg,rating_count',
            'customer:id,first_name,last_name,email',
        ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('reviewer_name', 'like', "%{$search}%")
                    ->orWhere('reviewer_email', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), ['pending', 'approved', 'rejected', 'spam'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('rating') && is_numeric($request->input('rating'))) {
            $query->where('rating', (int) $request->input('rating'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        $reviews = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $rawStatusCounts = Review::toBase()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $rawStarCounts = Review::toBase()
            ->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $starBreakdown = [
            5 => (int) ($rawStarCounts[5] ?? 0),
            4 => (int) ($rawStarCounts[4] ?? 0),
            3 => (int) ($rawStarCounts[3] ?? 0),
            2 => (int) ($rawStarCounts[2] ?? 0),
            1 => (int) ($rawStarCounts[1] ?? 0),
        ];

        $approvedCount = (int) ($rawStatusCounts['approved'] ?? 0);
        $avgRating = $approvedCount > 0
            ? round((float) Review::where('status', 'approved')->avg('rating'), 2)
            : 0.00;

        $metrics = [
            'total_reviews' => (int) $rawStatusCounts->sum(),
            'pending_count' => (int) ($rawStatusCounts['pending'] ?? 0),
            'approved_count' => $approvedCount,
            'rejected_count' => (int) ($rawStatusCounts['rejected'] ?? 0),
            'spam_count' => (int) ($rawStatusCounts['spam'] ?? 0),
            'average_rating' => $avgRating,
            'star_breakdown' => $starBreakdown,
        ];

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'metrics' => $metrics,
            'filters' => $request->only(['search', 'status', 'rating', 'product_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'nullable|exists:customers,id',
            'order_id' => 'nullable|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:3000',
            'photos' => 'nullable|array',
            'photos.*' => 'string|max:2048',
            'reviewer_name' => 'required|string|max:255',
            'reviewer_email' => 'nullable|email|max:255',
            'status' => 'nullable|in:pending,approved,rejected,spam',
            'is_verified_purchase' => 'boolean',
        ]);

        $review = Review::create([
            ...$validated,
            'status' => $validated['status'] ?? 'pending',
            'is_verified_purchase' => $validated['is_verified_purchase'] ?? false,
        ]);

        if ($review->status === 'approved') {
            $review->product?->updateRatingMetrics();
        }

        return redirect()->back()->with('success', 'Review submitted successfully.');
    }

    public function updateStatus(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,spam',
        ]);

        $review->update(['status' => $validated['status']]);
        $review->product?->updateRatingMetrics();

        return redirect()->back()->with('success', "Review marked as {$validated['status']}.");
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'merchant_reply' => 'required|string|max:2000',
        ]);

        $review->reply($validated['merchant_reply']);

        return redirect()->back()->with('success', 'Merchant reply posted successfully.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $product = $review->product;
        $review->delete();

        $product?->updateRatingMetrics();

        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}
