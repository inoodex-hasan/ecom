<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CourierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourierController extends Controller
{
    public function __construct(
        protected CourierService $courierService
    ) {}

    /**
     * Dispatch an order parcel to the specified courier provider.
     */
    public function dispatchOrder(Request $request, Order $order): RedirectResponse
    {
        if (! $request->has('provider') && $request->has('courier_provider')) {
            $request->merge(['provider' => $request->input('courier_provider')]);
        }

        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:steadfast,pathao,redx'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->courierService->dispatchOrder(
            $order,
            $validated['provider'],
            ['note' => $validated['note'] ?? null]
        );

        if ($result['success']) {
            $order->refresh();
            $providerLabel = ucfirst($result['provider']);

            return redirect()->back()->with(
                'success',
                "Order #{$order->order_number} booked with {$providerLabel}! Consignment ID: {$result['consignment_id']}, Tracking: {$result['tracking_code']}."
            );
        }

        return redirect()->back()->withErrors([
            'courier' => $result['message'] ?? 'Failed to book parcel with courier.',
        ]);
    }

    /**
     * Re-sync live delivery status from the courier network.
     */
    public function syncStatus(Order $order): RedirectResponse
    {
        $result = $this->courierService->checkStatus($order);

        return redirect()->back()->with('success', $result['message'] ?? 'Courier status synchronized.');
    }

    /**
     * Printable Shipping Label / Courier Manifest Slip.
     */
    public function printLabel(Order $order): Response
    {
        $order->load(['customer', 'items.product']);

        $storeInfo = [
            'name' => Setting::get('site_name', config('app.name', 'BD E-Commerce')),
            'phone' => Setting::get('store_phone', '01700000000'),
            'address' => Setting::get('store_address', 'Dhaka, Bangladesh'),
            'email' => Setting::get('store_email', 'support@ecom.test'),
        ];

        return Inertia::render('Admin/Orders/CourierLabel', [
            'order' => $order,
            'storeInfo' => $storeInfo,
            'trackingUrl' => $this->courierService->getTrackingUrl($order),
        ]);
    }
}
