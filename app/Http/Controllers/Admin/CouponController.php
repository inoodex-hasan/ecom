<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Coupon::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type') && in_array($request->input('type'), ['percentage', 'fixed_cart', 'free_shipping'], true)) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $now = now();
            match ($request->input('status')) {
                'active' => $query->where('is_active', true)
                    ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now)),
                'expired' => $query->where('expires_at', '<', $now),
                'disabled' => $query->where('is_active', false),
                'upcoming' => $query->where('starts_at', '>', $now),
                default => null,
            };
        }

        $coupons = $query->withCount('usages')
            ->orderBy('created_at', 'desc')
            ->get();

        $allCoupons = Coupon::all();
        $now = now();

        $metrics = [
            'total_coupons' => $allCoupons->count(),
            'active_coupons' => $allCoupons->filter(fn ($c) => $c->is_active && (! $c->expires_at || $c->expires_at >= $now))->count(),
            'total_redemptions' => (int) $allCoupons->sum('total_used'),
            'total_discount_claimed' => (float) CouponUsage::sum('discount_amount'),
        ];

        $categories = Category::where('is_active', true)->select('id', 'name')->get();

        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => $coupons,
            'metrics' => $metrics,
            'categories' => $categories,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:percentage,fixed_cart,free_shipping',
            'value' => 'nullable|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'integer|exists:categories,id',
        ]);

        if ($validated['type'] === 'free_shipping') {
            $validated['value'] = 0;
        }

        $validated['code'] = strtoupper(trim($validated['code']));

        Coupon::create($validated);

        return redirect()->back()->with('success', 'Coupon created successfully.');
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,'.$coupon->id,
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:percentage,fixed_cart,free_shipping',
            'value' => 'nullable|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'integer|exists:categories,id',
        ]);

        if ($validated['type'] === 'free_shipping') {
            $validated['value'] = 0;
        }

        $validated['code'] = strtoupper(trim($validated['code']));

        $coupon->update($validated);

        return redirect()->back()->with('success', 'Coupon updated successfully.');
    }

    public function toggleStatus(Coupon $coupon): RedirectResponse
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        $statusText = $coupon->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "Coupon '{$coupon->code}' {$statusText}.");
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $code = $coupon->code;
        $coupon->delete();

        return redirect()->back()->with('success', "Coupon '{$code}' deleted successfully.");
    }

    public function validateApi(Request $request, CouponService $couponService): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
            'customer_id' => 'nullable|integer',
            'items' => 'nullable|array',
            'shipping_fee' => 'nullable|numeric|min:0',
        ]);

        $result = $couponService->validateAndApply(
            code: $request->input('code'),
            cartSubtotal: (float) $request->input('subtotal'),
            customerId: $request->input('customer_id') ? (int) $request->input('customer_id') : null,
            cartItems: $request->input('items', []),
            shippingFee: (float) $request->input('shipping_fee', 0.0)
        );

        return response()->json($result);
    }
}
