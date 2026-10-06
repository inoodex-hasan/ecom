<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::with(['category:id,name', 'brand:id,name', 'variants'])->withCount('variants');

        // Search by Product Name, SKU, or Variant SKU
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('variants', function ($vq) use ($search) {
                        $vq->where('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        // Product Type filter
        if ($request->filled('product_type')) {
            $query->where('product_type', $request->input('product_type'));
        }

        // Stock Status filter
        if ($request->filled('stock_status')) {
            $status = $request->input('stock_status');
            if ($status === 'out_of_stock') {
                $query->where('stock_quantity', '<=', 0);
            } elseif ($status === 'low_stock') {
                $query->where('stock_quantity', '>', 0)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
            } elseif ($status === 'in_stock') {
                $query->whereColumn('stock_quantity', '>', 'low_stock_threshold');
            }
        }

        // Sorting
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');
        $allowedSorts = ['name', 'sku', 'stock_quantity', 'price', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        $products = $query->paginate(15)->withQueryString();

        // Calculate KPI Metrics
        $totalStandaloneSkus = Product::where('has_variants', false)->count();
        $totalVariantSkus = ProductVariant::count();
        $totalSkus = $totalStandaloneSkus + $totalVariantSkus;

        $totalStockUnits = Product::sum('stock_quantity');

        $lowStockCount = Product::where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        $outOfStockCount = Product::where('stock_quantity', '<=', 0)->count();

        // Recent Audit Transactions
        $transactions = InventoryTransaction::with([
            'product:id,name,sku,primary_image,unit',
            'productVariant:id,sku,option_values',
            'user:id,name',
        ])
            ->recent()
            ->paginate(15, ['*'], 'tx_page')
            ->withQueryString();

        return Inertia::render('Admin/Inventory/Index', [
            'products' => $products,
            'transactions' => $transactions,
            'categories' => Category::where('is_active', true)->get(['id', 'name']),
            'filters' => $request->only(['search', 'category', 'product_type', 'stock_status', 'sort', 'direction']),
            'metrics' => [
                'total_skus' => $totalSkus,
                'total_units' => (int) $totalStockUnits,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
            ],
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'mode' => 'required|in:add,remove,set',
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string|in:restock,physical_count,damaged,customer_return,order_fulfillment,loss_theft,expired,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::findOrFail($validated['product_id']);
            $variant = ! empty($validated['product_variant_id'])
                ? ProductVariant::where('product_id', $product->id)->findOrFail($validated['product_variant_id'])
                : null;

            $target = $variant ?: $product;
            $prevStock = (int) $target->stock_quantity;
            $qtyInput = (int) $validated['quantity'];

            $newStock = match ($validated['mode']) {
                'add' => $prevStock + $qtyInput,
                'remove' => max(0, $prevStock - $qtyInput),
                'set' => max(0, $qtyInput),
            };

            $delta = $newStock - $prevStock;

            if ($delta === 0) {
                return;
            }

            $type = $delta > 0 ? 'in' : ($delta < 0 ? 'out' : 'adjustment');

            $target->update(['stock_quantity' => $newStock]);

            // If variant was updated, re-sync parent product total stock
            if ($variant) {
                $product->update([
                    'stock_quantity' => (int) $product->variants()->sum('stock_quantity'),
                ]);
            }

            InventoryTransaction::create([
                'product_id' => $product->id,
                'product_variant_id' => $variant ? $variant->id : null,
                'user_id' => Auth::id(),
                'type' => $type,
                'quantity_change' => $delta,
                'previous_stock' => $prevStock,
                'new_stock' => $newStock,
                'reason' => $validated['reason'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()->back()->with('success', 'Stock level adjusted successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'inventory-report-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Product Name', 'SKU', 'Variant Details', 'Category', 'Unit', 'Current Stock', 'Low Stock Threshold', 'Stock Status', 'Unit Price']);

            Product::with(['category', 'variants'])->cursor()->each(function ($product) use ($handle) {
                if ($product->has_variants && $product->variants->isNotEmpty()) {
                    foreach ($product->variants as $variant) {
                        $variantOptions = is_array($variant->option_values)
                            ? implode(', ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($variant->option_values), $variant->option_values))
                            : '';

                        $status = $variant->stock_quantity <= 0 ? 'Out of Stock' : ($variant->stock_quantity <= $product->low_stock_threshold ? 'Low Stock' : 'In Stock');

                        fputcsv($handle, [
                            $product->name,
                            $variant->sku,
                            $variantOptions,
                            $product->category?->name ?? 'N/A',
                            $product->unit,
                            $variant->stock_quantity,
                            $product->low_stock_threshold,
                            $status,
                            $variant->price,
                        ]);
                    }
                } else {
                    $status = $product->stock_quantity <= 0 ? 'Out of Stock' : ($product->stock_quantity <= $product->low_stock_threshold ? 'Low Stock' : 'In Stock');

                    fputcsv($handle, [
                        $product->name,
                        $product->sku,
                        'Single Product',
                        $product->category?->name ?? 'N/A',
                        $product->unit,
                        $product->stock_quantity,
                        $product->low_stock_threshold,
                        $status,
                        $product->price,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
