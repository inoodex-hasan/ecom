<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        protected CouponService $couponService,
    ) {}

    /**
     * Validate coupon code against cart and return discount breakdown.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
            'customer_id' => 'nullable|integer',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|integer',
            'items.*.category_id' => 'nullable|integer',
            'items.*.price' => 'nullable|numeric',
            'items.*.quantity' => 'nullable|integer',
        ]);

        $result = $this->couponService->validateAndApply(
            code: $validated['code'],
            cartSubtotal: (float) $validated['subtotal'],
            customerId: $validated['customer_id'] ?? null,
            cartItems: $validated['items'] ?? [],
        );

        if (! $result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'coupon_id' => $result['coupon']->id,
                'code' => $result['coupon']->code,
                'type' => $result['coupon']->type,
                'discount_amount' => $result['discount'],
                'free_shipping' => $result['coupon']->type === 'free_shipping',
                'new_subtotal' => max(0, (float) $validated['subtotal'] - $result['discount']),
            ],
        ]);
    }
}
