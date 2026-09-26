<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    public function export(Request $request): BinaryFileResponse
    {
        $format = $request->input('format', 'xlsx') === 'csv' ? 'csv' : 'xlsx';
        $fileName = 'orders-export-'.now()->format('Y-m-d').'.'.$format;

        return Excel::download(new OrdersExport($request->all()), $fileName);
    }

    public function index(Request $request): Response
    {
        $query = Order::with('customer')->withCount('items');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        $rawCounts = Order::toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusCounts = [
            'all' => (int) $rawCounts->sum(),
            'pending' => (int) ($rawCounts['pending'] ?? 0),
            'processing' => (int) ($rawCounts['processing'] ?? 0),
            'shipped' => (int) ($rawCounts['shipped'] ?? 0),
            'delivered' => (int) ($rawCounts['delivered'] ?? 0),
            'cancelled' => (int) ($rawCounts['cancelled'] ?? 0),
        ];

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'filters' => $request->only(['search', 'status', 'payment_status']),
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['customer', 'items.product']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        if ($oldStatus === $newStatus) {
            return redirect()->back()->with('success', "Order status is already {$newStatus}.");
        }

        DB::transaction(function () use ($order, $oldStatus, $newStatus) {
            $updateData = ['status' => $newStatus];

            if ($newStatus === 'shipped' && ! $order->shipped_at) {
                $updateData['shipped_at'] = now();
            } elseif ($newStatus === 'delivered' && ! $order->delivered_at) {
                $updateData['delivered_at'] = now();
            }

            $order->update($updateData);

            // Automatic stock replenishment when order is cancelled
            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                $order->loadMissing('items');
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
                    }
                }
            }

            // Re-deduct stock if an order is uncancelled
            if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                $order->loadMissing('items');
                foreach ($order->items as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->decrement('stock_quantity', $item->quantity);
                    }
                }
            }

            // Sync customer aggregates
            $order->customer?->recalculateTotals();
        });

        return redirect()->back()->with('success', "Order status changed to {$newStatus}.");
    }

    public function updatePaymentStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:unpaid,paid,refunded,failed',
        ]);

        $newPaymentStatus = $validated['payment_status'];

        DB::transaction(function () use ($order, $newPaymentStatus) {
            $updateData = ['payment_status' => $newPaymentStatus];

            if ($newPaymentStatus === 'paid' && ! $order->paid_at) {
                $updateData['paid_at'] = now();
            }

            $order->update($updateData);

            // Re-sync customer lifetime metrics accurately
            $order->customer?->recalculateTotals();
        });

        return redirect()->back()->with('success', "Payment status changed to {$newPaymentStatus}.");
    }

    public function invoice(Order $order): Response
    {
        $order->load(['customer', 'items.product']);

        return Inertia::render('Admin/Orders/Invoice', [
            'order' => $order,
        ]);
    }
}
