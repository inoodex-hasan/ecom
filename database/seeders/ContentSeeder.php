<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Page;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pages
        Page::updateOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => 'About Us',
                'meta_title' => 'About Us - Our Story & Mission',
                'meta_description' => 'Learn more about our brand, our commitment to quality, and our mission.',
                'is_published' => true,
                'content' => '<h2>Welcome to Our Store</h2><p>We are dedicated to providing the highest quality products with exceptional customer service. Founded with a vision to redefine modern commerce, our brand bridges timeless craftsmanship with state-of-the-art innovation.</p><h3>Our Mission</h3><p>To empower customers with hand-curated selections, transparent pricing, and lightning-fast fulfillment across the globe.</p><h3>Why Choose Us</h3><ul><li>Premium verified authentic products</li><li>Fast & reliable door-to-door delivery</li><li>24/7 dedicated customer care</li><li>Hassle-free 30-day money-back guarantee</li></ul>',
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'terms-and-conditions'],
            [
                'title' => 'Terms & Conditions',
                'meta_title' => 'Terms & Conditions',
                'meta_description' => 'Read our terms of service and usage conditions.',
                'is_published' => true,
                'content' => '<h2>Terms & Conditions</h2><p>By accessing or purchasing from our platform, you agree to comply with our general terms of service. All prices are listed in standard currency and are subject to availability.</p>',
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => 'Privacy Policy',
                'meta_title' => 'Privacy Policy',
                'meta_description' => 'Understand how we collect, safeguard, and process your personal information.',
                'is_published' => true,
                'content' => '<h2>Privacy Policy</h2><p>Your privacy is paramount. We only collect the minimal personal information necessary to fulfill your orders and enhance your shopping journey.</p>',
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'shipping-and-returns'],
            [
                'title' => 'Shipping & Returns',
                'meta_title' => 'Shipping, Delivery & Return Policy',
                'meta_description' => 'Explore our delivery timelines, courier partners, and simple return process.',
                'is_published' => true,
                'content' => '<h2>Shipping & Returns</h2><p>We partner with leading couriers including Steadfast and Pathao to ensure swift, tracked parcel dispatch. Standard delivery takes 2-4 business days.</p>',
            ]
        );

        // 2. Blog Posts
        BlogPost::updateOrCreate(
            ['slug' => 'top-trends-this-season'],
            [
                'title' => 'Top Fashion & Lifestyle Trends of the Season',
                'excerpt' => 'Discover the curated must-haves dominating global runways and everyday style.',
                'cover_image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1200&auto=format&fit=crop&q=80',
                'category' => 'Trends',
                'author_name' => 'Fashion Editor',
                'read_time_minutes' => 4,
                'is_published' => true,
                'published_at' => now()->subDays(2),
                'meta_title' => 'Top Trends of the Season',
                'meta_description' => 'Must-have wardrobe staples and trending essentials.',
                'content' => '<h2>The Evolution of Everyday Luxury</h2><p>This season is all about effortless versatility, sustainable fabrics, and clean minimalist aesthetics. Discover our top curated picks designed to elevate your everyday rotation.</p>',
            ]
        );

        BlogPost::updateOrCreate(
            ['slug' => 'ultimate-guide-to-smart-shopping'],
            [
                'title' => 'The Ultimate Guide to Smart Online Shopping',
                'excerpt' => 'Tips and tricks to score maximum discounts, flash sale perks, and verified genuine items.',
                'cover_image' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200&auto=format&fit=crop&q=80',
                'category' => 'Guides',
                'author_name' => 'Shopping Concierge',
                'read_time_minutes' => 3,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'meta_title' => 'Guide to Smart Online Shopping',
                'meta_description' => 'Save big on flash deals and promotional campaigns.',
                'content' => '<h2>How to Maximize Your Savings</h2><p>Stacking coupons, tracking flash sales, and early-bird notifications can save you up to 50% on your orders. Learn the insider strategies today.</p>',
            ]
        );
    }
}
