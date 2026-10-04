<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = Category::with(['parent:id,name', 'children:id,name,parent_id'])
            ->withCount(['products', 'children'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $totalCategories = $categories->count();
        $rootCategories = $categories->whereNull('parent_id')->count();
        $subCategories = $categories->whereNotNull('parent_id')->count();
        $activeCategories = $categories->where('is_active', true)->count();
        $totalProducts = $categories->sum('products_count');

        $metrics = [
            'total_categories' => $totalCategories,
            'root_categories' => $rootCategories,
            'sub_categories' => $subCategories,
            'active_categories' => $activeCategories,
            'total_products' => $totalProducts,
        ];

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'metrics' => $metrics,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:2048',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        Category::create([
            ...$validated,
            'slug' => $slug,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,'.$category->id,
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    if ($value && (int) $value === (int) $category->id) {
                        $fail('A category cannot be its own parent.');
                    }
                },
            ],
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:2048',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $category->update([
            ...$validated,
            'slug' => $slug,
        ]);

        return redirect()->back()->with('success', 'Category updated successfully.');
    }

    public function toggleStatus(Category $category): RedirectResponse
    {
        $category->update([
            'is_active' => ! $category->is_active,
        ]);

        return redirect()->back()->with('success', 'Category status updated.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $path = $request->file('image')->store('categories', 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    public function destroy(Category $category): RedirectResponse
    {
        // If category has products, detach or reassign
        if ($category->products()->exists()) {
            $category->products()->update(['category_id' => null]);
        }

        // If category has children, promote them to root level
        if ($category->children()->exists()) {
            $category->children()->update(['parent_id' => null]);
        }

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully.');
    }
}
