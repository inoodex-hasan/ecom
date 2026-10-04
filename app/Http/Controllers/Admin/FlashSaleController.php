<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FlashSaleController extends Controller
{
    public function index(Request $request): Response
    {
        $sales = FlashSale::with(['items.product:id,name,sku,price,primary_image'])
            ->withCount('items')
            ->orderBy('starts_at', 'desc')
            ->get();

        $allSales = FlashSale::with('items')->get();
        $now = now();

        $metrics = [
            'total_campaigns' => $allSales->count(),
            'active_running' => $allSales->filter(fn ($s) => $s->is_active && $s->starts_at <= $now && $s->ends_at >= $now)->count(),
            'upcoming' => $allSales->filter(fn ($s) => $s->is_active && $s->starts_at > $now)->count(),
            'total_items_on_sale' => FlashSaleItem::count(),
            'total_units_claimed' => (int) FlashSaleItem::sum('sold_count'),
        ];

        // Compact product list for modal selector
        $products = Product::where('status', 'published')
            ->get(['id', 'name', 'sku', 'price', 'compare_price', 'primary_image']);

        return Inertia::render('Admin/FlashSales/Index', [
            'flashSales' => $sales,
            'metrics' => $metrics,
            'availableProducts' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:flash_sales,slug',
            'banner_image' => 'nullable|string|max:2048',
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.flash_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.quantity_limit' => 'nullable|integer|min:1',
        ]);

        DB::transaction(function () use ($validated) {
            $slug = ! empty($validated['slug'])
                ? Str::slug($validated['slug'])
                : Str::slug($validated['title']).'-'.Str::lower(Str::random(5));

            $flashSale = FlashSale::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'banner_image' => $validated['banner_image'] ?? null,
                'description' => $validated['description'] ?? null,
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $discount = $item['discount_percentage'] ?? null;
                if (! $discount && $product && $product->price > 0) {
                    $discount = round((($product->price - $item['flash_price']) / $product->price) * 100, 1);
                }

                $flashSale->items()->create([
                    'product_id' => $item['product_id'],
                    'flash_price' => $item['flash_price'],
                    'discount_percentage' => $discount,
                    'quantity_limit' => $item['quantity_limit'] ?? null,
                    'sold_count' => 0,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Flash sale campaign launched successfully.');
    }

    public function update(Request $request, FlashSale $flashSale): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:flash_sales,slug,'.$flashSale->id,
            'banner_image' => 'nullable|string|max:2048',
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.flash_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.quantity_limit' => 'nullable|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $flashSale) {
            $slug = ! empty($validated['slug'])
                ? Str::slug($validated['slug'])
                : $flashSale->slug;

            $flashSale->update([
                'title' => $validated['title'],
                'slug' => $slug,
                'banner_image' => $validated['banner_image'] ?? null,
                'description' => $validated['description'] ?? null,
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Sync items: keep existing sold count if item was already in sale
            $existingSoldMap = $flashSale->items->pluck('sold_count', 'product_id')->toArray();
            $flashSale->items()->delete();

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $discount = $item['discount_percentage'] ?? null;
                if (! $discount && $product && $product->price > 0) {
                    $discount = round((($product->price - $item['flash_price']) / $product->price) * 100, 1);
                }

                $flashSale->items()->create([
                    'product_id' => $item['product_id'],
                    'flash_price' => $item['flash_price'],
                    'discount_percentage' => $discount,
                    'quantity_limit' => $item['quantity_limit'] ?? null,
                    'sold_count' => $existingSoldMap[$item['product_id']] ?? 0,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Flash sale campaign updated successfully.');
    }

    public function toggleStatus(FlashSale $flashSale): RedirectResponse
    {
        $flashSale->update(['is_active' => ! $flashSale->is_active]);

        return redirect()->back()->with('success', 'Flash sale status updated.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:3072',
        ]);

        $path = $request->file('image')->store('flash-sales', 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    public function destroy(FlashSale $flashSale): RedirectResponse
    {
        $flashSale->delete();

        return redirect()->back()->with('success', 'Flash sale campaign removed.');
    }
}
