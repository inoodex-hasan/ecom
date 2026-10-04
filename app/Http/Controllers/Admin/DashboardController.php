<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $paidOrdersCount = Order::where('payment_status', 'paid')->count();
        $totalRevenue = (float) Order::where('payment_status', 'paid')->sum('total');
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $totalCustomers = Customer::count();
        $totalProducts = Product::count();
        $lowStockCount = Product::lowStock()->count();

        $avgOrderValue = $paidOrdersCount > 0 ? round($totalRevenue / $paidOrdersCount, 2) : 0.00;
        $fulfilledOrdersCount = Order::whereIn('status', ['delivered', 'shipped'])->count();
        $fulfillmentRate = $totalOrders > 0 ? round(($fulfilledOrdersCount / $totalOrders) * 100, 1) : 100.0;

        // Recent Orders with items count & customer
        $recentOrders = Order::with('customer')
            ->withCount('items')
            ->latest()
            ->take(5)
            ->get();

        // Low stock products
        $lowStockProducts = Product::with('category')
            ->lowStock()
            ->take(5)
            ->get();

        // Top Selling Products with revenue calculation
        $topProducts = OrderItem::select('product_id', 'product_name', 'sku', 'image', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total) as revenue'))
            ->groupBy('product_id', 'product_name', 'sku', 'image')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        $maxSold = $topProducts->max('total_sold') ?: 1;
        $topProducts->transform(function ($item) use ($maxSold) {
            $item->progress_percentage = round(($item->total_sold / $maxSold) * 100);

            return $item;
        });

        // 12-Month Sales History (single query for all 12 months)
        $historyStartDate = Carbon::now()->subMonths(11)->startOfMonth();
        $recentHistoryOrders = Order::toBase()
            ->where('created_at', '>=', $historyStartDate)
            ->select('created_at', 'payment_status', 'total')
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->created_at)->format('Y-m'));

        $months = collect(range(11, 0))->map(function ($i) use ($recentHistoryOrders) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $monthOrders = $recentHistoryOrders->get($key, collect());

            $revenue = (float) $monthOrders->where('payment_status', 'paid')->sum('total');
            $ordersCount = $monthOrders->count();

            return [
                'month' => $date->format('M'),
                'full_month' => $date->format('M Y'),
                'revenue' => $revenue,
                'orders' => $ordersCount,
            ];
        });

        // Order Status Distribution for Donut Chart (single grouped query)
        $statusCounts = Order::toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusDistribution = [
            'delivered' => (int) ($statusCounts['delivered'] ?? 0),
            'shipped' => (int) ($statusCounts['shipped'] ?? 0),
            'processing' => (int) ($statusCounts['processing'] ?? 0),
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
        ];

        // Category breakdown
        $categories = Category::withCount('products')->get()->map(function ($cat) {
            return [
                'name' => $cat->name,
                'products_count' => $cat->products_count,
            ];
        });

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'total_customers' => $totalCustomers,
                'total_products' => $totalProducts,
                'low_stock_count' => $lowStockCount,
                'avg_order_value' => $avgOrderValue,
                'fulfillment_rate' => $fulfillmentRate,
            ],
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
            'topProducts' => $topProducts,
            'statusDistribution' => $statusDistribution,
            'salesChart' => [
                'categories' => $months->pluck('month'),
                'full_categories' => $months->pluck('full_month'),
                'revenue' => $months->pluck('revenue'),
                'orders' => $months->pluck('orders'),
            ],
            'categoryDistribution' => $categories,
        ]);
    }
}
