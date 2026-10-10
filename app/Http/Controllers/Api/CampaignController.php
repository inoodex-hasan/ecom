<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    /**
     * Active & upcoming promotional campaigns.
     */
    public function index(): JsonResponse
    {
        $now = now();

        $campaigns = FlashSale::where('is_active', true)
            ->where('ends_at', '>=', $now)
            ->orderBy('starts_at')
            ->with(['items.product' => function ($q) {
                $q->select('id', 'name', 'slug', 'price', 'compare_price', 'primary_image', 'rating_avg', 'rating_count', 'badge_label');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    /**
     * Single campaign details by slug or id with participating items.
     */
    public function show(string $slug): JsonResponse
    {
        $campaign = FlashSale::where('is_active', true)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug);
                if (is_numeric($slug)) {
                    $q->orWhere('id', (int) $slug);
                }
            })
            ->with(['items.product' => function ($q) {
                $q->select('id', 'name', 'slug', 'price', 'compare_price', 'primary_image', 'rating_avg', 'rating_count', 'badge_label', 'stock_quantity');
            }])
            ->first();

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found or expired.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $campaign,
        ]);
    }
}
