<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\FraudBlacklist;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        protected CouponService $couponService,
    ) {}

    /**
     * Submit checkout order from Next.js storefront.
     */
    public function checkout(Request $request): JsonResponse
    {
        // 0. Pre-normalize frontend parameters for seamless compatibility
        $input = $request->all();

        // Map full name -> first_name & last_name
        if (! empty($input['name']) && empty($input['first_name'])) {
            $nameParts = explode(' ', trim($input['name']), 2);
            $input['first_name'] = $nameParts[0] ?: 'Customer';
            $input['last_name'] = $nameParts[1] ?? '';
        }

        // Map address -> address_line_1
        if (! empty($input['address']) && empty($input['address_line_1'])) {
            $input['address_line_1'] = $input['address'];
        }

        // Map postcode -> postal_code
        if (! empty($input['postcode']) && empty($input['postal_code'])) {
            $input['postal_code'] = $input['postcode'];
        }

        // Map payment -> payment_method
        if (! empty($input['payment']) && empty($input['payment_method'])) {
            $input['payment_method'] = $input['payment'];
        }

        // Map wallet -> sender_number
        if (! empty($input['wallet']) && empty($input['sender_number'])) {
            $input['sender_number'] = $input['wallet'];
        }

        // Map trxid / transaction_id -> trx
        if (! empty($input['trxid']) && empty($input['trx'])) {
            $input['trx'] = $input['trxid'];
        } elseif (! empty($input['transaction_id']) && empty($input['trx'])) {
            $input['trx'] = $input['transaction_id'];
        }

        // Map items.*.id -> items.*.product_id & items.*.qty -> items.*.quantity
        if (! empty($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as $idx => $item) {
                if (is_array($item)) {
                    if (! empty($item['id']) && empty($item['product_id'])) {
                        $numericId = preg_replace('/\D/', '', (string) $item['id']);
                        $input['items'][$idx]['product_id'] = $numericId !== '' ? (int) $numericId : $item['id'];
                    }
                    if (! empty($item['qty']) && empty($item['quantity'])) {
                        $input['items'][$idx]['quantity'] = (int) $item['qty'];
                    }
                }
            }
        }

        $request->merge($input);

        $validated = $request->validate([
            'first_name' => 'required_without:name|nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:200',
            'email' => 'nullable|email|max:150',
            'phone' => 'required|string|max:30',
            'address_line_1' => 'required_without:address|nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'area' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'postcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|in:cod,bkash,nagad,rocket,card,bank,online',
            'sender_number' => 'nullable|string|max:30',
            'trx' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
            'delivery_charge' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // 1. Resolve or create Customer (by email if provided, by phone, or create guest)
            $cleanPhone = preg_replace('/\D/', '', $validated['phone']);
            $email = ! empty($validated['email'])
                ? $validated['email']
                : "guest_{$cleanPhone}@ecom.local";

            $customer = null;
            if (! empty($validated['email'])) {
                $customer = Customer::where('email', $validated['email'])->first();
            }
            if (! $customer && ! empty($validated['phone'])) {
                $customer = Customer::where('phone', $validated['phone'])->first();
            }

            if (! $customer) {
                $customer = Customer::create([
                    'first_name' => $validated['first_name'] ?: 'Customer',
                    'last_name' => $validated['last_name'] ?? '',
                    'email' => $email,
                    'phone' => $validated['phone'],
                    'address_line_1' => $validated['address_line_1'],
                    'address_line_2' => $validated['address_line_2'] ?? $request->input('area'),
                    'city' => $validated['city'],
                    'postal_code' => $validated['postal_code'] ?? null,
                    'country' => $validated['country'] ?? 'Bangladesh',
                    'status' => 'active',
                ]);
            } else {
                $customer->update(array_filter([
                    'address_line_1' => $customer->address_line_1 ?: $validated['address_line_1'],
                    'city' => $customer->city ?: $validated['city'],
                ]));
            }

            // 2. Validate product stock and calculate subtotal
            $subtotal = 0;
            $orderItemsData = [];

            foreach ($validated['items'] as $itemInput) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->find($itemInput['product_id']);

                if (! $product || $product->status !== 'published') {
                    return response()->json([
                        'success' => false,
                        'message' => "Product '{$product?->name}' is currently unavailable.",
                    ], 422);
                }

                $qty = (int) $itemInput['quantity'];
                $unitPrice = (float) $product->price;
                $sku = $product->sku;
                $productName = $product->name;

                // Handle variant if specified
                $variant = null;
                if (! empty($itemInput['variant_id'])) {
                    $variant = ProductVariant::lockForUpdate()->find($itemInput['variant_id']);
                    if ($variant && $variant->product_id === $product->id) {
                        $sku = $variant->sku;
                        if ($variant->price > 0) {
                            $unitPrice = (float) $variant->price;
                        }
                        if ($variant->stock < $qty) {
                            return response()->json([
                                'success' => false,
                                'message' => "Insufficient stock for {$product->name} ({$sku}). Available: {$variant->stock}.",
                            ], 422);
                        }
                    }
                } else {
                    if ($product->stock_quantity < $qty) {
                        return response()->json([
                            'success' => false,
                            'message' => "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}.",
                        ], 422);
                    }
                }

                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'product_id' => $product->id,
                    'product_name' => $productName,
                    'sku' => $sku,
                    'image' => $product->primary_image,
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'total' => $lineTotal,
                ];
            }

            // 3. Coupon Validation & Discount
            $discount = 0;
            $appliedCoupon = null;
            $freeShipping = false;

            if (! empty($validated['coupon_code'])) {
                $couponRes = $this->couponService->validateAndApply(
                    code: $validated['coupon_code'],
                    cartSubtotal: $subtotal,
                    customerId: $customer->id,
                    cartItems: $orderItemsData,
                );

                if ($couponRes['valid']) {
                    $discount = (float) $couponRes['discount'];
                    $appliedCoupon = $couponRes['coupon'];
                    $freeShipping = $appliedCoupon->type === 'free_shipping';
                }
            }

            // 4. Shipping Calculation
            if ($request->has('delivery_charge') || $request->has('shipping_cost')) {
                $shippingCost = max(0, (float) $request->input('delivery_charge', $request->input('shipping_cost')));
            } else {
                $freeThreshold = (float) Setting::get('free_shipping_threshold', Setting::get('shipping.free_shipping_threshold', 150));
                $defaultShipping = (float) Setting::get('flat_shipping_rate', Setting::get('shipping.default_fee', 100));
                $shippingCost = ($freeShipping || $subtotal >= $freeThreshold) ? 0.0 : $defaultShipping;
            }

            // 5. Total
            $total = max(0, ($subtotal - $discount) + $shippingCost);

            // 6. Fraud Screening Check
            $isBlacklisted = FraudBlacklist::isBlacklisted('phone', $validated['phone'])
                || (! empty($validated['email']) && FraudBlacklist::isBlacklisted('email', $validated['email']));
            $fraudRiskLevel = $isBlacklisted ? 'high' : 'low';
            $fraudStatus = $isBlacklisted ? 'suspect' : 'passed';

            // 7. Generate unique order number
            $orderNumber = 'ORD-'.date('Ymd').'-'.strtoupper(Str::random(4));

            $shippingAddress = [
                'name' => trim(($validated['first_name'] ?: 'Customer').' '.($validated['last_name'] ?? '')),
                'phone' => $validated['phone'],
                'email' => $email,
                'address_line_1' => $validated['address_line_1'],
                'address_line_2' => $validated['address_line_2'] ?? '',
                'area' => $request->input('area', ''),
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'] ?? '',
                'country' => $validated['country'] ?? 'Bangladesh',
                'delivery_type' => $request->input('delivery', 'home'),
                'wallet_number' => $validated['sender_number'] ?? null,
                'trx_id' => $validated['trx'] ?? null,
            ];

            $paymentMethod = $validated['payment_method'] ?? 'cod';
            $paymentNote = '';
            if (! empty($validated['sender_number']) || ! empty($validated['trx'])) {
                $paymentNote = "\n[Payment: Wallet: ".($validated['sender_number'] ?? 'N/A').' | TrxID: '.($validated['trx'] ?? 'N/A').']';
            }
            $finalNotes = trim(($validated['notes'] ?? '').$paymentNote) ?: null;

            // 8. Create Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => 0,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'shipping_address' => $shippingAddress,
                'billing_address' => $shippingAddress,
                'notes' => $finalNotes,
                'fraud_risk_level' => $fraudRiskLevel,
                'fraud_status' => $fraudStatus,
                'fraud_notes' => $isBlacklisted ? 'Customer contact matched fraud blacklist records.' : null,
                'advance_payment_method' => in_array($paymentMethod, ['bkash', 'nagad', 'rocket']) ? $paymentMethod : null,
                'advance_transaction_id' => $validated['trx'] ?? null,
                'advance_payment_status' => ! empty($validated['trx']) ? 'unverified' : 'none',
                'courier_cod_amount' => $paymentMethod === 'cod' ? $total : 0.0,
            ]);

            // 9. Create Order Items & Decrement Stock
            foreach ($orderItemsData as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'],
                    'image' => $item['image'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'total' => $item['total'],
                ]);

                // Deduct stock and log audit transaction
                $product = $item['product'];
                $previousStock = $product->stock_quantity;
                $newStock = max(0, $previousStock - $item['quantity']);
                $product->decrement('stock_quantity', $item['quantity']);

                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'product_variant_id' => $item['variant']?->id,
                    'type' => 'out',
                    'quantity_change' => -$item['quantity'],
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reason' => 'online_sale',
                    'reference_number' => $order->order_number,
                    'notes' => "Order #{$order->order_number} checkout deduction",
                ]);

                if ($item['variant']) {
                    $item['variant']->decrement('stock', $item['quantity']);
                }
            }

            // 10. Record Coupon Usage
            if ($appliedCoupon) {
                $this->couponService->recordUsage(
                    coupon: $appliedCoupon,
                    discountAmount: $discount,
                    customerId: $customer->id,
                    orderId: $order->id,
                );
            }

            // 11. Update Customer Aggregate Totals
            $customer->increment('total_orders');
            $customer->increment('total_spent', $total);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'subtotal' => $order->subtotal,
                    'discount' => $order->discount,
                    'shipping_cost' => $order->shipping_cost,
                    'total' => $order->total,
                    'shipping_address' => $order->shipping_address,
                    'created_at' => $order->created_at,
                ],
            ], 201);
        });
    }

    /**
     * Track order status for Next.js tracking page.
     */
    public function track(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items'])
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found with provided tracking number.',
            ], 404);
        }

        // Optional phone verification for security if phone parameter sent
        if ($request->filled('phone')) {
            $shippingPhone = $order->shipping_address['phone'] ?? '';
            if (substr(preg_replace('/\D/', '', $shippingPhone), -6) !== substr(preg_replace('/\D/', '', $request->query('phone')), -6)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number does not match order record.',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'courier_provider' => $order->courier_provider,
                'courier_tracking_code' => $order->courier_tracking_code,
                'total' => $order->total,
                'created_at' => $order->created_at,
                'shipped_at' => $order->shipped_at,
                'delivered_at' => $order->delivered_at,
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'image' => $item->image,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                ]),
            ],
        ]);
    }
}
