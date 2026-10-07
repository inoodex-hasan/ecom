<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class InitController extends Controller
{
    /**
     * Store public branding, contact info, and configuration for Next.js.
     */
    public function settings(): JsonResponse
    {
        $publicSettings = [
            'store_name' => Setting::get('general.store_name', config('app.name', 'Modern Store')),
            'store_tagline' => Setting::get('general.store_tagline', 'Your Premier Shopping Destination'),
            'store_logo' => Setting::get('general.store_logo'),
            'store_favicon' => Setting::get('general.store_favicon'),
            'currency_symbol' => Setting::get('currency_symbol', Setting::get('localization.currency_symbol', '৳')),
            'currency_code' => Setting::get('currency_code', Setting::get('localization.currency', 'BDT')),
            'contact_email' => Setting::get('contact.email', 'support@ecom.test'),
            'contact_phone' => Setting::get('contact.phone', '+1 (555) 019-2834'),
            'contact_address' => Setting::get('contact.address', '100 Market Street, Suite 500, San Francisco, CA'),
            'social_facebook' => Setting::get('social.facebook'),
            'social_instagram' => Setting::get('social.instagram'),
            'social_twitter' => Setting::get('social.twitter'),
            'social_youtube' => Setting::get('social.youtube'),
            'free_shipping_threshold' => (float) Setting::get('shipping.free_shipping_threshold', 100),
            'default_shipping_fee' => (float) Setting::get('shipping.default_fee', 10),
            'announcement_bar' => Setting::get('marketing.announcement_text', 'Free nationwide express shipping on all orders over ৳1,000!'),
        ];

        return response()->json([
            'success' => true,
            'data' => $publicSettings,
        ]);
    }

    /**
     * Full aggregated homepage feed payload for high-speed Next.js SSR / ISR.
     */
    public function homeFeed(): JsonResponse
    {
        $now = now();

        // 1. Hero Banners
        $banners = Banner::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('sort_order')
            ->get(['id', 'title', 'subtitle', 'badge_text', 'image_url', 'mobile_image_url', 'link_url', 'button_text', 'placement']);

        // 2. Featured / Root Categories
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->withCount(['products' => fn ($q) => $q->where('status', 'published')])
            ->get(['id', 'name', 'slug', 'image', 'icon', 'description']);

        // 3. Active Running Campaigns with live countdown
        $activeCampaigns = FlashSale::where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->with(['items.product' => function ($q) {
                $q->select('id', 'name', 'slug', 'price', 'compare_price', 'primary_image', 'rating_avg', 'rating_count');
            }])
            ->get(['id', 'title', 'slug', 'banner_image', 'description', 'starts_at', 'ends_at']);

        // 4. Curated Merchandising Showcase Products
        $hotProducts = Product::where('status', 'published')
            ->where('is_hot', true)
            ->latest()
            ->take(8)
            ->get(['id', 'name', 'slug', 'price', 'compare_price', 'primary_image', 'rating_avg', 'rating_count', 'badge_label', 'stock_quantity']);

        $trendingProducts = Product::where('status', 'published')
            ->where('is_trending', true)
            ->latest()
            ->take(8)
            ->get(['id', 'name', 'slug', 'price', 'compare_price', 'primary_image', 'rating_avg', 'rating_count', 'badge_label', 'stock_quantity']);

        $newArrivals = Product::where('status', 'published')
            ->where('is_new_arrival', true)
            ->latest()
            ->take(8)
            ->get(['id', 'name', 'slug', 'price', 'compare_price', 'primary_image', 'rating_avg', 'rating_count', 'badge_label', 'stock_quantity']);

        // Fallback featured products if no badges set
        $featuredProducts = Product::where('status', 'published')
            ->latest()
            ->take(8)
            ->get(['id', 'name', 'slug', 'price', 'compare_price', 'primary_image', 'rating_avg', 'rating_count', 'badge_label', 'stock_quantity']);

        // 5. Recent Blog Articles
        $recentBlogs = BlogPost::published()
            ->latest('published_at')
            ->take(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'cover_image', 'category', 'author_name', 'read_time_minutes', 'published_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'banners' => $banners,
                'categories' => $categories,
                'active_campaigns' => $activeCampaigns,
                'hot_products' => $hotProducts,
                'trending_products' => $trendingProducts,
                'new_arrivals' => $newArrivals,
                'featured_products' => $featuredProducts,
                'recent_blogs' => $recentBlogs,
            ],
        ]);
    }
}
