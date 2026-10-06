<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Banner::query();

        if ($request->filled('placement')) {
            $query->where('placement', $request->input('placement'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%")
                    ->orWhere('badge_text', 'like', "%{$search}%");
            });
        }

        $banners = $query->orderBy('placement')
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        $metrics = [
            'total_banners' => Banner::count(),
            'hero_slides' => Banner::where('placement', 'hero_slider')->count(),
            'home_promos' => Banner::where('placement', 'home_banner')->count(),
            'active_count' => Banner::where('is_active', true)->count(),
        ];

        return Inertia::render('Admin/Banners/Index', [
            'banners' => $banners,
            'metrics' => $metrics,
            'filters' => $request->only(['placement', 'status', 'search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'badge_text' => 'nullable|string|max:100',
            'image_url' => 'required|string|max:2048',
            'mobile_image_url' => 'nullable|string|max:2048',
            'button_text' => 'nullable|string|max:100',
            'link_url' => 'nullable|string|max:2048',
            'placement' => 'required|in:hero_slider,home_banner,category_banner,popup_promo',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        Banner::create($validated);

        return redirect()->back()->with('success', 'Banner created successfully.');
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'badge_text' => 'nullable|string|max:100',
            'image_url' => 'required|string|max:2048',
            'mobile_image_url' => 'nullable|string|max:2048',
            'button_text' => 'nullable|string|max:100',
            'link_url' => 'nullable|string|max:2048',
            'placement' => 'required|in:hero_slider,home_banner,category_banner,popup_promo',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $banner->update($validated);

        return redirect()->back()->with('success', 'Banner updated successfully.');
    }

    public function toggleStatus(Banner $banner): RedirectResponse
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return redirect()->back()->with('success', 'Banner status updated.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072', 'dimensions:max_width=4000,max_height=4000'],
        ]);

        $path = $request->file('image')->store('banners', 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $img = $banner->image_url;
        $mobileImg = $banner->mobile_image_url;

        $banner->delete();

        foreach ([$img, $mobileImg] as $url) {
            if ($url) {
                $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH) ?? $url);
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        return redirect()->back()->with('success', 'Banner deleted successfully.');
    }
}
