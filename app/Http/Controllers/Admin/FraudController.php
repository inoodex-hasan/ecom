<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudBlacklist;
use App\Models\Order;
use App\Models\Setting;
use App\Services\FraudCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FraudController extends Controller
{
    public function __construct(
        protected FraudCheckService $fraudService
    ) {}

    public function index(Request $request): Response
    {
        $riskFilter = $request->input('risk', 'all');
        $statusFilter = $request->input('status', 'all');
        $search = $request->input('search');

        // 1. High-level Fraud Dashboard Metrics
        $totalScreened = Order::whereNotNull('fraud_checked_at')->count();
        $highRiskCount = Order::where('fraud_risk_level', 'high')->count();
        $mediumRiskCount = Order::where('fraud_risk_level', 'medium')->count();
        $lowRiskCount = Order::where('fraud_risk_level', 'low')->count();
        $blockedCount = Order::where('fraud_status', 'blocked')->count();

        // Estimated delivery courier loss prevented (assuming ৳120 return delivery fee per prevented fake order)
        $estimatedSavedBdt = $blockedCount * 120;

        // 2. Query Orders with Risk Filters
        $ordersQuery = Order::with('customer')
            ->when($riskFilter !== 'all', fn ($q) => $q->where('fraud_risk_level', $riskFilter))
            ->when($statusFilter !== 'all', fn ($q) => $q->where('fraud_status', $statusFilter))
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('order_number', 'like', "%{$search}%")
                        ->orWhere('shipping_address->phone', 'like', "%{$search}%")
                        ->orWhere('shipping_address->name', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('fraud_score')
            ->latest();

        $flaggedOrders = $ordersQuery->paginate(12)->withQueryString();

        // 3. Blacklist / Whitelist Registry
        $blacklist = FraudBlacklist::with('creator')->latest()->get();

        // 4. Current Settings
        $settings = [
            'fraud_check_enabled' => (bool) Setting::get('fraud_check_enabled', true),
            'fraud_cod_threshold' => (float) Setting::get('fraud_cod_threshold', 5000),
            'fraud_advance_fee_inside_dhaka' => (float) Setting::get('fraud_advance_fee_inside_dhaka', 100),
            'fraud_advance_fee_outside_dhaka' => (float) Setting::get('fraud_advance_fee_outside_dhaka', 150),
            'fraud_auto_flag_high_risk' => (bool) Setting::get('fraud_auto_flag_high_risk', true),
            'fraud_courier_provider' => (string) Setting::get('fraud_courier_provider', 'steadfast'),
            'fraud_courier_api_endpoint' => (string) Setting::get('fraud_courier_api_endpoint', 'https://api.steadfast.com.bd/v1/courier-check'),
            'fraud_courier_api_key' => (string) Setting::get('fraud_courier_api_key', ''),
            'steadfast_api_key' => (string) Setting::get('steadfast_api_key', ''),
            'steadfast_secret_key' => (string) Setting::get('steadfast_secret_key', ''),
            'pathao_store_id' => (string) Setting::get('pathao_store_id', ''),
        ];

        return Inertia::render('Admin/Fraud/Index', [
            'metrics' => [
                'total_screened' => $totalScreened,
                'high_risk' => $highRiskCount,
                'medium_risk' => $mediumRiskCount,
                'low_risk' => $lowRiskCount,
                'blocked' => $blockedCount,
                'estimated_saved_bdt' => $estimatedSavedBdt,
                'blacklist_count' => $blacklist->where('list_type', 'blacklist')->count(),
                'whitelist_count' => $blacklist->where('list_type', 'whitelist')->count(),
            ],
            'orders' => $flaggedOrders,
            'blacklist' => $blacklist,
            'settings' => $settings,
            'filters' => [
                'risk' => $riskFilter,
                'status' => $statusFilter,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Standalone phone lookup endpoint for manual merchant verification.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:25'],
        ]);

        $result = $this->fraudService->lookupPhone($request->input('phone'));

        return response()->json($result);
    }

    /**
     * Re-run fraud analysis on an order.
     */
    public function recheck(Order $order): RedirectResponse
    {
        $assessment = $this->fraudService->evaluateOrder($order);

        $order->update([
            'fraud_score' => $assessment['score'],
            'fraud_risk_level' => $assessment['risk_level'],
            'fraud_status' => $assessment['status'],
            'fraud_flags' => $assessment['flags'],
            'advance_delivery_charge' => $assessment['advance_required'] ? $assessment['suggested_advance_fee'] : null,
            'advance_payment_status' => $assessment['advance_required'] ? 'pending' : 'none',
            'fraud_checked_at' => now(),
            'fraud_notes' => $assessment['recommended_action'],
        ]);

        return redirect()->back()->with('success', "Order #{$order->order_number} re-scanned. Risk Score: {$assessment['score']}/100.");
    }

    /**
     * Manually approve an order and mark as safe/verified.
     */
    public function verify(Order $order): RedirectResponse
    {
        $order->update([
            'fraud_status' => 'verified',
            'fraud_notes' => 'Manually marked as safe and verified by administrator.',
        ]);

        return redirect()->back()->with('success', "Order #{$order->order_number} marked as Verified & Safe.");
    }

    /**
     * Request advance delivery charge via bKash/Nagad.
     */
    public function requestAdvance(Request $request, Order $order): RedirectResponse
    {
        $fee = (float) ($request->input('fee') ?? $request->input('amount') ?? $request->input('advance_delivery_charge') ?? 150);

        $order->update([
            'advance_delivery_charge' => $fee,
            'advance_payment_status' => 'pending',
            'fraud_status' => 'advance_requested',
            'fraud_notes' => "Customer requested to pay ৳{$fee} advance delivery charge via bKash/Nagad.",
        ]);

        return redirect()->back()->with('success', "Advance delivery fee of ৳{$fee} requested for Order #{$order->order_number}.");
    }

    /**
     * Confirm advance delivery charge payment with bKash/Nagad Transaction ID.
     */
    public function confirmAdvance(Request $request, Order $order): RedirectResponse
    {
        $method = strtolower((string) ($request->input('advance_payment_method') ?? $request->input('method') ?? 'bkash'));
        $trxId = (string) ($request->input('advance_transaction_id') ?? $request->input('transaction_id') ?? '');

        if (empty($trxId)) {
            return redirect()->back()->withErrors(['transaction_id' => 'Transaction ID is required.']);
        }

        $order->update([
            'advance_payment_method' => $method,
            'advance_transaction_id' => strtoupper(trim($trxId)),
            'advance_payment_status' => 'paid',
            'fraud_status' => 'verified',
            'fraud_notes' => "Advance delivery fee confirmed via {$method} (TrxID: {$trxId}). Safe to fulfill.",
        ]);

        return redirect()->back()->with('success', "Advance fee confirmed for Order #{$order->order_number}. Order verified for dispatch.");
    }

    /**
     * Block / Cancel an order and optionally add phone to Blacklist.
     */
    public function blockOrder(Request $request, Order $order): RedirectResponse
    {
        $reason = $request->input('reason') ?? 'Flagged as high-risk fraudulent order.';
        $addToBlacklist = $request->has('add_to_blacklist') ? $request->boolean('add_to_blacklist') : true;

        $order->update([
            'status' => 'cancelled',
            'fraud_status' => 'blocked',
            'fraud_notes' => $reason,
        ]);

        $phone = $order->shipping_address['phone'] ?? $order->customer?->phone ?? null;
        $normalizedPhone = $this->fraudService->normalizeBdPhone($phone);

        if ($addToBlacklist && $normalizedPhone) {
            FraudBlacklist::updateOrCreate(
                ['type' => 'phone', 'value' => $normalizedPhone],
                [
                    'list_type' => 'blacklist',
                    'reason' => $reason,
                    'created_by' => $request->user()?->id,
                ]
            );
        }

        return redirect()->back()->with('success', "Order #{$order->order_number} cancelled and flagged as blocked.");
    }

    /**
     * Add entry to Blacklist or Whitelist.
     */
    public function storeBlacklist(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:phone,email,ip,address'],
            'value' => ['required', 'string', 'max:255'],
            'list_type' => ['required', 'string', 'in:blacklist,whitelist'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $value = trim($validated['value']);
        if ($validated['type'] === 'phone') {
            $value = $this->fraudService->normalizeBdPhone($value) ?? $value;
        }

        FraudBlacklist::updateOrCreate(
            ['type' => $validated['type'], 'value' => $value],
            [
                'list_type' => $validated['list_type'],
                'reason' => $validated['reason'] ?? ($validated['list_type'] === 'whitelist' ? 'Trusted Customer' : 'Fraud risk'),
                'created_by' => $request->user()?->id,
            ]
        );

        $label = ucfirst($validated['list_type']);

        return redirect()->back()->with('success', "{$value} added to {$label} successfully.");
    }

    /**
     * Remove entry from Blacklist/Whitelist.
     */
    public function destroyBlacklist(FraudBlacklist $blacklist): RedirectResponse
    {
        $val = $blacklist->value;
        $blacklist->delete();

        return redirect()->back()->with('success', "{$val} removed from registry.");
    }

    /**
     * Update fraud engine thresholds and courier API keys.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fraud_check_enabled' => ['required', 'boolean'],
            'fraud_cod_threshold' => ['required', 'numeric', 'min:500'],
            'fraud_advance_fee_inside_dhaka' => ['required', 'numeric', 'min:0'],
            'fraud_advance_fee_outside_dhaka' => ['required', 'numeric', 'min:0'],
            'fraud_auto_flag_high_risk' => ['required', 'boolean'],
            'fraud_courier_provider' => ['nullable', 'string'],
            'fraud_courier_api_endpoint' => ['nullable', 'string', 'max:255'],
            'fraud_courier_api_key' => ['nullable', 'string', 'max:255'],
            'steadfast_api_key' => ['nullable', 'string', 'max:255'],
            'steadfast_secret_key' => ['nullable', 'string', 'max:255'],
            'pathao_store_id' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated as $key => $val) {
            Setting::set($key, $val ?? '', is_bool($val) ? 'boolean' : 'string', 'fraud');
        }

        return redirect()->back()->with('success', 'Bangladeshi Fraud Shield settings updated successfully.');
    }
}
