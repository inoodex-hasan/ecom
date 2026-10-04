<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;

class CouponService
{
    /**
     * Validate a coupon against a shopping cart and customer.
     *
     * @param  array<int, array<string, mixed>>  $cartItems
     * @return array{valid: bool, discount: float, shipping_discount: float, coupon: ?Coupon, message: string}
     */
    public function validateAndApply(
        string $code,
        float $cartSubtotal,
        ?int $customerId = null,
        array $cartItems = [],
        float $shippingFee = 0.0
    ): array {
        $cleanCode = strtoupper(trim($code));

        if (empty($cleanCode)) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'shipping_discount' => 0.0,
                'coupon' => null,
                'message' => 'Please enter a coupon code.',
            ];
        }

        $coupon = Coupon::whereRaw('UPPER(code) = ?', [$cleanCode])->first();

        if (! $coupon) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'shipping_discount' => 0.0,
                'coupon' => null,
                'message' => "Coupon code '{$cleanCode}' is invalid.",
            ];
        }

        if (! $coupon->is_active) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'shipping_discount' => 0.0,
                'coupon' => $coupon,
                'message' => 'This coupon is currently inactive.',
            ];
        }

        $now = now();

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'shipping_discount' => 0.0,
                'coupon' => $coupon,
                'message' => 'This promotional coupon has not started yet.',
            ];
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'shipping_discount' => 0.0,
                'coupon' => $coupon,
                'message' => 'This coupon code has expired.',
            ];
        }

        if ($coupon->usage_limit_total !== null && $coupon->total_used >= $coupon->usage_limit_total) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'shipping_discount' => 0.0,
                'coupon' => $coupon,
                'message' => 'This coupon has reached its maximum total redemptions.',
            ];
        }

        if ($customerId && $coupon->usage_limit_per_customer) {
            $customerUsages = CouponUsage::where('coupon_id', $coupon->id)
                ->where('customer_id', $customerId)
                ->count();

            if ($customerUsages >= $coupon->usage_limit_per_customer) {
                return [
                    'valid' => false,
                    'discount' => 0.0,
                    'shipping_discount' => 0.0,
                    'coupon' => $coupon,
                    'message' => 'You have already reached the usage limit for this coupon.',
                ];
            }
        }

        if ($coupon->min_spend !== null && $cartSubtotal < $coupon->min_spend) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'shipping_discount' => 0.0,
                'coupon' => $coupon,
                'message' => sprintf('Minimum order spend of $%.2f is required to use this coupon.', $coupon->min_spend),
            ];
        }

        // Category restrictions check
        if (! empty($coupon->applicable_categories) && ! empty($cartItems)) {
            $allowedCatIds = array_map('intval', (array) $coupon->applicable_categories);
            $hasMatchingCategory = false;
            foreach ($cartItems as $item) {
                if (isset($item['category_id']) && in_array((int) $item['category_id'], $allowedCatIds, true)) {
                    $hasMatchingCategory = true;
                    break;
                }
            }

            if (! $hasMatchingCategory) {
                return [
                    'valid' => false,
                    'discount' => 0.0,
                    'shipping_discount' => 0.0,
                    'coupon' => $coupon,
                    'message' => 'This coupon is not valid for any items currently in your cart.',
                ];
            }
        }

        // Product restrictions check
        if (! empty($coupon->applicable_products) && ! empty($cartItems)) {
            $allowedProdIds = array_map('intval', (array) $coupon->applicable_products);
            $hasMatchingProduct = false;
            foreach ($cartItems as $item) {
                if (isset($item['product_id']) && in_array((int) $item['product_id'], $allowedProdIds, true)) {
                    $hasMatchingProduct = true;
                    break;
                }
            }

            if (! $hasMatchingProduct) {
                return [
                    'valid' => false,
                    'discount' => 0.0,
                    'shipping_discount' => 0.0,
                    'coupon' => $coupon,
                    'message' => 'This coupon is restricted to specific products not present in your cart.',
                ];
            }
        }

        $discount = 0.0;
        $shippingDiscount = 0.0;

        switch ($coupon->type) {
            case 'percentage':
                $discount = round(($cartSubtotal * $coupon->value) / 100, 2);
                if ($coupon->max_discount !== null && $discount > $coupon->max_discount) {
                    $discount = (float) $coupon->max_discount;
                }
                $discount = min($discount, $cartSubtotal);
                break;

            case 'fixed_cart':
                $discount = min((float) $coupon->value, $cartSubtotal);
                break;

            case 'free_shipping':
                $discount = 0.0;
                $shippingDiscount = max(0.0, $shippingFee);
                break;
        }

        return [
            'valid' => true,
            'discount' => $discount,
            'shipping_discount' => $shippingDiscount,
            'coupon' => $coupon,
            'message' => "Coupon '{$coupon->code}' applied successfully!",
        ];
    }

    /**
     * Record a coupon usage against an order and customer.
     */
    public function recordUsage(
        Coupon $coupon,
        float $discountAmount,
        ?int $customerId = null,
        ?int $orderId = null
    ): CouponUsage {
        $usage = CouponUsage::create([
            'coupon_id' => $coupon->id,
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'discount_amount' => $discountAmount,
            'used_at' => now(),
        ]);

        $coupon->increment('total_used');

        return $usage;
    }
}
