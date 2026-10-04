<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate(['image' => 'required|image|max:2048']);
        $path = $request->file('image')->store('products', 'public');

        return response()->json(['url' => Storage::url($path)]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $format = $request->input('format', 'xlsx') === 'csv' ? 'csv' : 'xlsx';
        $fileName = 'products-export-'.now()->format('Y-m-d').'.'.$format;

        return Excel::download(new ProductsExport($request->all()), $fileName);
    }

    public function index(Request $request): Response
    {
        $query = Product::with(['category', 'brand', 'variants'])->withCount('variants');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('product_type')) {
            $query->where('product_type', $request->input('product_type'));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['name', 'price', 'stock_quantity', 'created_at', 'status', 'product_type'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $products = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'categories' => Category::where('is_active', true)->get(['id', 'name']),
            'brands' => Brand::where('is_active', true)->get(['id', 'name']),
            'filters' => $request->only(['search', 'product_type', 'category', 'status', 'low_stock', 'sort', 'direction']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'product_type' => 'nullable|string|in:standard,apparel,building_material,liquid,electronics,digital',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'min_order_quantity' => 'nullable|numeric|min:0.01',
            'quantity_step' => 'nullable|numeric|min:0.01',
            'unit_coverage_value' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|string|max:20',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'dimension_unit' => 'nullable|string|max:20',
            'attributes' => 'nullable|array',
            'has_variants' => 'boolean',
            'status' => 'required|in:draft,published,archived',
            'is_featured' => 'boolean',
            'badge_label' => 'nullable|string|max:50',
            'is_hot' => 'boolean',
            'is_trending' => 'boolean',
            'is_new_arrival' => 'boolean',
            'primary_image' => 'nullable|string|max:2048',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'string',
            'variants' => 'nullable|array',
            'variants.*.sku' => 'required_with:variants|string|max:100',
            'variants.*.price' => 'required_with:variants|numeric|min:0',
            'variants.*.compare_price' => 'nullable|numeric|min:0',
            'variants.*.cost_price' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'required_with:variants|integer|min:0',
            'variants.*.option_values' => 'required_with:variants|array',
            'variants.*.image' => 'nullable|string',
            'variants.*.is_active' => 'boolean',
        ]);

        $variantsData = $validated['variants'] ?? [];
        unset($validated['variants']);

        $product = Product::create([
            ...$validated,
            'product_type' => $validated['product_type'] ?? 'standard',
            'unit' => $validated['unit'] ?? 'piece',
            'min_order_quantity' => $validated['min_order_quantity'] ?? 1,
            'quantity_step' => $validated['quantity_step'] ?? 1,
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(5)),
        ]);

        if (! empty($validated['gallery_images'])) {
            foreach ($validated['gallery_images'] as $idx => $imgPath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imgPath,
                    'is_primary' => $idx === 0 && empty($product->primary_image),
                    'sort_order' => $idx + 1,
                ]);
            }
        }

        if ($product->has_variants && ! empty($variantsData)) {
            $totalVariantStock = 0;
            foreach ($variantsData as $variant) {
                $qty = (int) ($variant['stock_quantity'] ?? 0);
                $totalVariantStock += $qty;
                $product->variants()->create([
                    'sku' => $variant['sku'],
                    'barcode' => $variant['barcode'] ?? null,
                    'price' => $variant['price'],
                    'compare_price' => $variant['compare_price'] ?? null,
                    'cost_price' => $variant['cost_price'] ?? null,
                    'stock_quantity' => $qty,
                    'image' => $variant['image'] ?? null,
                    'option_values' => $variant['option_values'] ?? [],
                    'is_active' => $variant['is_active'] ?? true,
                ]);
            }
            $product->update(['stock_quantity' => $totalVariantStock]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,'.$product->id,
            'product_type' => 'nullable|string|in:standard,apparel,building_material,liquid,electronics,digital',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'min_order_quantity' => 'nullable|numeric|min:0.01',
            'quantity_step' => 'nullable|numeric|min:0.01',
            'unit_coverage_value' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'nullable|string|max:20',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'dimension_unit' => 'nullable|string|max:20',
            'attributes' => 'nullable|array',
            'has_variants' => 'boolean',
            'status' => 'required|in:draft,published,archived',
            'is_featured' => 'boolean',
            'badge_label' => 'nullable|string|max:50',
            'is_hot' => 'boolean',
            'is_trending' => 'boolean',
            'is_new_arrival' => 'boolean',
            'primary_image' => 'nullable|string|max:2048',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.sku' => 'required_with:variants|string|max:100',
            'variants.*.price' => 'required_with:variants|numeric|min:0',
            'variants.*.compare_price' => 'nullable|numeric|min:0',
            'variants.*.cost_price' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'required_with:variants|integer|min:0',
            'variants.*.option_values' => 'required_with:variants|array',
            'variants.*.image' => 'nullable|string',
            'variants.*.is_active' => 'boolean',
        ]);

        $variantsData = $validated['variants'] ?? [];
        unset($validated['variants']);

        $product->update($validated);

        if ($product->has_variants && ! empty($variantsData)) {
            $existingVariantIds = [];
            foreach ($variantsData as $variant) {
                if (! empty($variant['id'])) {
                    $existingVariant = $product->variants()->find($variant['id']);
                    if ($existingVariant) {
                        $existingVariant->update([
                            'sku' => $variant['sku'],
                            'barcode' => $variant['barcode'] ?? null,
                            'price' => $variant['price'],
                            'compare_price' => $variant['compare_price'] ?? null,
                            'cost_price' => $variant['cost_price'] ?? null,
                            'stock_quantity' => $variant['stock_quantity'] ?? 0,
                            'image' => $variant['image'] ?? null,
                            'option_values' => $variant['option_values'] ?? [],
                            'is_active' => $variant['is_active'] ?? true,
                        ]);
                        $existingVariantIds[] = $existingVariant->id;

                        continue;
                    }
                }

                $newVar = $product->variants()->create([
                    'sku' => $variant['sku'],
                    'barcode' => $variant['barcode'] ?? null,
                    'price' => $variant['price'],
                    'compare_price' => $variant['compare_price'] ?? null,
                    'cost_price' => $variant['cost_price'] ?? null,
                    'stock_quantity' => $variant['stock_quantity'] ?? 0,
                    'image' => $variant['image'] ?? null,
                    'option_values' => $variant['option_values'] ?? [],
                    'is_active' => $variant['is_active'] ?? true,
                ]);
                $existingVariantIds[] = $newVar->id;
            }

            // Remove deleted variants
            $product->variants()->whereNotIn('id', $existingVariantIds)->delete();
            $product->update(['stock_quantity' => (int) $product->variants()->sum('stock_quantity')]);
        } elseif (! $product->has_variants) {
            $product->variants()->delete();
        }

        return redirect()->back()->with('success', 'Product updated successfully.');
    }

    public function quickUpdate(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'stock_quantity' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
            'badge_label' => 'nullable|string|max:50',
            'is_hot' => 'nullable|boolean',
            'is_trending' => 'nullable|boolean',
            'is_new_arrival' => 'nullable|boolean',
            'unit' => 'nullable|string|max:50',
            'product_type' => 'nullable|string|in:standard,apparel,building_material,liquid,electronics,digital',
        ]);

        if ($product->has_variants && isset($validated['stock_quantity'])) {
            unset($validated['stock_quantity']);
        }

        $product->update(array_filter($validated, fn ($val) => ! is_null($val)));

        return redirect()->back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $hasActiveOrders = $product->orderItems()
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['pending', 'processing']))
            ->exists();

        if ($hasActiveOrders) {
            return redirect()->back()->with('error', 'Cannot delete a product with pending or processing orders. Please archive or draft it instead.');
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }
}
