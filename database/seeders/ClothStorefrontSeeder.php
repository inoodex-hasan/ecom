<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClothStorefrontSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedSettings();
            $this->seedBanners();
            $this->cleanPreviousData();
            $categories = $this->seedCategories();
            $brands = $this->seedBrands();
            $this->seedProductsAndDeals($categories, $brands);
        });
    }

    private function seedSettings(): void
    {
        $settings = [
            'store_name' => 'Loomora',
            'general.store_name' => 'Loomora',
            'general.store_tagline' => 'Lifestyle Ltd',
            'store_email' => 'support@loomora.com',
            'contact.email' => 'support@loomora.com',
            'store_phone' => '+880-1700-000000',
            'contact.phone' => '+880-1700-000000',
            'contact.address' => 'House 12, Road 5, Dhanmondi, Dhaka-1205, Bangladesh',
            'currency_symbol' => '৳',
            'currency_code' => 'BDT',
            'localization.currency_symbol' => '৳',
            'localization.currency' => 'BDT',
            'marketing.announcement_text' => 'Free nationwide express shipping on all orders over ৳1,000!',
            'free_shipping_threshold' => 1000.00,
            'shipping.free_shipping_threshold' => 1000.00,
            'default_shipping_fee' => 100.00,
            'shipping.default_fee' => 100.00,
            'tax_rate_percentage' => 0.00,
        ];

        foreach ($settings as $key => $val) {
            Setting::set($key, $val);
        }
    }

    private function seedBanners(): void
    {
        Banner::query()->delete();

        Banner::create([
            'title' => 'fresh drops',
            'subtitle' => 'Just landed — the latest styles to refresh your look',
            'badge_text' => 'New Season',
            'image_url' => '/storage/banners/fresh-drops.avif',
            'link_url' => '/new-in',
            'button_text' => 'Shop Now',
            'placement' => 'hero_slider',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Banner::create([
            'title' => 'steal of a deal',
            'subtitle' => 'Limited-time prices across the whole store',
            'badge_text' => 'Best Deals',
            'image_url' => '/storage/banners/steal-deal.avif',
            'link_url' => '/best-deals',
            'button_text' => 'Shop Now',
            'placement' => 'hero_slider',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Banner::create([
            'title' => 'Sundry Blossom',
            'subtitle' => 'Elevate Your Style. Accessories for a brighter you.',
            'badge_text' => 'Trending',
            'image_url' => '/storage/banners/sundry-blossom.png',
            'link_url' => '/accessories',
            'button_text' => 'Explore',
            'placement' => 'home_banner',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        Banner::create([
            'title' => 'Festive Collection',
            'subtitle' => 'Exclusive Festive Edit — Elevate Your Celebration',
            'badge_text' => 'Festival',
            'image_url' => '/storage/banners/festive-collection.png',
            'link_url' => '/puja-2026',
            'button_text' => 'Shop Now',
            'placement' => 'category_banner',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        Banner::create([
            'title' => 'Exclusive Collection',
            'subtitle' => 'Curated trends and seasonal highlights',
            'badge_text' => 'Exclusive',
            'image_url' => '/storage/videos/cloth.mp4',
            'link_url' => '/new-in',
            'button_text' => 'Explore',
            'placement' => 'home_video',
            'sort_order' => 5,
            'is_active' => true,
        ]);
    }

    private function cleanPreviousData(): void
    {
        // Remove all previous reviews
        Review::query()->delete();

        // Remove flash sale items
        FlashSaleItem::query()->delete();

        // Remove all previous products (cascades variants, transactions, images)
        Product::query()->delete();

        // Remove all previous categories
        Category::query()->delete();
    }

    private function seedCategories(): array
    {
        // Exactly 5 clean categories matching the frontend
        $categoriesData = [
            ['name' => 'Men', 'slug' => 'men', 'image' => '/storage/categories/men.avif', 'icon' => 'Shirt', 'description' => 'Shirts, panjabis, denim & more'],
            ['name' => 'Women', 'slug' => 'women', 'image' => '/storage/categories/women.avif', 'icon' => 'Sparkles', 'description' => 'Kurtis, kameez, sarees & more'],
            ['name' => 'Kids', 'slug' => 'kids', 'image' => '/storage/categories/kids.jpg', 'icon' => 'Boxes', 'description' => 'Playful styles for little ones'],
            ['name' => 'Winter Wear', 'slug' => 'winter-wear', 'image' => '/storage/categories/winter-wear.avif', 'icon' => 'Flame', 'description' => 'Jackets, hoodies & sweaters'],
            ['name' => 'Accessories', 'slug' => 'accessories', 'image' => '/storage/categories/accessories.png', 'icon' => 'Tag', 'description' => 'Bags, scarves & daily essentials'],
        ];

        $categoryMap = [];
        foreach ($categoriesData as $index => $item) {
            $cat = Category::create([
                'name' => $item['name'],
                'slug' => $item['slug'],
                'image' => $item['image'],
                'icon' => $item['icon'],
                'description' => $item['description'],
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
            $categoryMap[$item['name']] = $cat;
        }

        return $categoryMap;
    }

    private function seedBrands(): array
    {
        $brandNames = [
            'Loomora',
            'DHEU',
            'Loomora Women',
            'Loomora Kids',
            'Loomora Signature',
        ];

        $brands = [];
        foreach ($brandNames as $name) {
            $brands[$name] = Brand::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                ]
            );
        }

        return $brands;
    }

    private function seedProductsAndDeals(array $categories, array $brands): void
    {
        // Exactly 10 minimal products with real images
        $productsList = [
            // 1. Men - Polo
            [
                'name' => 'Mens Short Sleeves Polo Shirt - Slim Fit',
                'category' => 'Men',
                'brand' => 'Loomora',
                'price' => 1290.00,
                'compare_price' => 1550.00,
                'cost_price' => 700.00,
                'sku' => 'CLOTH-M01',
                'image' => '/storage/products/mens-polo.avif',
                'badge' => 'New',
                'is_new_arrival' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_trending' => false,
                'sizes' => ['36', '38', '40', '42'],
                'fabric' => 'Cotton & Spandex',
                'fit' => 'Slim Fit',
            ],
            // 2. Men - Denim
            [
                'name' => 'Mens Dark Wash Denim Pant - Slim Fit',
                'category' => 'Men',
                'brand' => 'DHEU',
                'price' => 1990.00,
                'compare_price' => 2390.00,
                'cost_price' => 1100.00,
                'sku' => 'CLOTH-M02',
                'image' => '/storage/products/mens-denim.jpg',
                'badge' => 'Trending',
                'is_new_arrival' => false,
                'is_featured' => true,
                'is_hot' => false,
                'is_trending' => true,
                'sizes' => ['30', '32', '34', '36'],
                'fabric' => 'Stretch Denim',
                'fit' => 'Slim Fit',
            ],
            // 3. Winter Wear - Bomber
            [
                'name' => 'Rust Bomber Jacket',
                'category' => 'Winter Wear',
                'brand' => 'Loomora',
                'price' => 2000.00,
                'compare_price' => 2500.00,
                'cost_price' => 1200.00,
                'sku' => 'CLOTH-W01',
                'image' => '/storage/products/rust-bomber.avif',
                'badge' => 'Hot Deal',
                'is_new_arrival' => false,
                'is_featured' => true,
                'is_hot' => true,
                'is_trending' => true,
                'sizes' => ['S', 'M', 'L', 'XL'],
                'fabric' => 'Polyester Shell & Fleece Lining',
                'fit' => 'Regular Fit',
            ],
            // 4. Winter Wear - Denim Jacket
            [
                'name' => 'Classic Denim Trucker Jacket',
                'category' => 'Winter Wear',
                'brand' => 'DHEU',
                'price' => 2199.00,
                'compare_price' => 2500.00,
                'cost_price' => 1300.00,
                'sku' => 'CLOTH-W02',
                'image' => '/storage/products/denim-trucker.avif',
                'badge' => 'Hot Deal',
                'is_new_arrival' => false,
                'is_featured' => true,
                'is_hot' => true,
                'is_trending' => false,
                'sizes' => ['M', 'L', 'XL'],
                'fabric' => 'Heavy Denim 12oz',
                'fit' => 'Classic Fit',
            ],
            // 5. Winter Wear - Sweatshirt
            [
                'name' => 'Everyday White Sweatshirt',
                'category' => 'Winter Wear',
                'brand' => 'Loomora',
                'price' => 6000.00,
                'compare_price' => 6900.00,
                'cost_price' => 3500.00,
                'sku' => 'CLOTH-W03',
                'image' => '/storage/products/white-sweatshirt.avif',
                'badge' => 'Hot Deal',
                'is_new_arrival' => false,
                'is_featured' => true,
                'is_hot' => true,
                'is_trending' => false,
                'sizes' => ['S', 'M', 'L'],
                'fabric' => 'Organic French Terry Cotton',
                'fit' => 'Relaxed Fit',
            ],
            // 6. Men - Cotton Tee
            [
                'name' => 'Grey Cotton Tee',
                'category' => 'Men',
                'brand' => 'Loomora',
                'price' => 2690.00,
                'compare_price' => 3500.00,
                'cost_price' => 1400.00,
                'sku' => 'CLOTH-M03',
                'image' => '/storage/products/grey-tee.avif',
                'badge' => 'Hot Deal',
                'is_new_arrival' => false,
                'is_featured' => false,
                'is_hot' => true,
                'is_trending' => true,
                'sizes' => ['M', 'L', 'XL'],
                'fabric' => '100% Combed Cotton',
                'fit' => 'Regular Fit',
            ],
            // 7. Women - Saree
            [
                'name' => 'Womens Saree - Royal Crimson',
                'category' => 'Women',
                'brand' => 'Loomora Women',
                'price' => 1550.00,
                'compare_price' => 1950.00,
                'cost_price' => 850.00,
                'sku' => 'CLOTH-FM01',
                'image' => '/storage/products/womens-saree.jpg',
                'badge' => 'New',
                'is_new_arrival' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_trending' => false,
                'sizes' => ['Free Size'],
                'fabric' => 'Premium Georgette Silk',
                'fit' => 'Traditional',
            ],
            // 8. Women - 2 Pcs Set
            [
                'name' => 'Womens 2 Pcs Set – Regular Fit',
                'category' => 'Women',
                'brand' => 'Loomora Women',
                'price' => 2890.00,
                'compare_price' => 3490.00,
                'cost_price' => 1600.00,
                'sku' => 'CLOTH-FM02',
                'image' => '/storage/products/womens-set.jpg',
                'badge' => 'New',
                'is_new_arrival' => true,
                'is_featured' => true,
                'is_hot' => false,
                'is_trending' => true,
                'sizes' => ['36', '38', '40'],
                'fabric' => 'Fine Cotton Voile',
                'fit' => 'Regular Fit',
            ],
            // 9. Kids - Winter Set
            [
                'name' => 'Kids Playful Winter Essential Set',
                'category' => 'Kids',
                'brand' => 'Loomora Kids',
                'price' => 1750.00,
                'compare_price' => 2100.00,
                'cost_price' => 900.00,
                'sku' => 'CLOTH-KD01',
                'image' => '/storage/products/kids-set.jpg',
                'badge' => 'New',
                'is_new_arrival' => true,
                'is_featured' => false,
                'is_hot' => false,
                'is_trending' => false,
                'sizes' => ['4Y', '6Y', '8Y'],
                'fabric' => 'Soft Brushed Fleece',
                'fit' => 'Comfort Fit',
            ],
            // 10. Accessories - Silk Scarf
            [
                'name' => 'Sundry Blossom Silk Scarf & Accessory',
                'category' => 'Accessories',
                'brand' => 'Loomora Signature',
                'price' => 1224.00,
                'compare_price' => 1450.00,
                'cost_price' => 600.00,
                'sku' => 'CLOTH-AC01',
                'image' => '/storage/products/silk-scarf.png',
                'badge' => 'Trending',
                'is_new_arrival' => false,
                'is_featured' => true,
                'is_hot' => false,
                'is_trending' => true,
                'sizes' => ['Standard'],
                'fabric' => 'Pure Mulberry Silk',
                'fit' => 'One Size',
            ],
        ];

        $dealProducts = [];

        foreach ($productsList as $pData) {
            $cat = $categories[$pData['category']];
            $brand = $brands[$pData['brand']];
            $slug = Str::slug($pData['name']);

            $product = Product::create([
                'product_type' => 'apparel',
                'name' => $pData['name'],
                'slug' => $slug,
                'sku' => $pData['sku'],
                'category_id' => $cat->id,
                'brand_id' => $brand->id,
                'price' => $pData['price'],
                'compare_price' => $pData['compare_price'],
                'cost_price' => $pData['cost_price'],
                'stock_quantity' => 75,
                'low_stock_threshold' => 10,
                'status' => 'published',
                'is_featured' => $pData['is_featured'],
                'is_new_arrival' => $pData['is_new_arrival'],
                'is_hot' => $pData['is_hot'],
                'is_trending' => $pData['is_trending'],
                'badge_label' => $pData['badge'],
                'primary_image' => $pData['image'],
                'short_description' => "Premium {$pData['name']} by {$pData['brand']}.",
                'description' => "Crafted from {$pData['fabric']}. Designed for an exceptional {$pData['fit']}.",
                'attributes' => [
                    'fabric' => $pData['fabric'],
                    'fit' => $pData['fit'],
                ],
                'has_variants' => count($pData['sizes']) > 1,
            ]);

            foreach ($pData['sizes'] as $size) {
                $variantSku = "{$pData['sku']}-{$size}";
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variantSku,
                    'price' => $pData['price'],
                    'compare_price' => $pData['compare_price'],
                    'cost_price' => $pData['cost_price'],
                    'stock_quantity' => 25,
                    'option_values' => [
                        'size' => $size,
                    ],
                    'is_active' => true,
                ]);
            }

            if ($pData['is_hot']) {
                $dealProducts[] = $product;
            }
        }

        // Daily Deals Campaign linking hot deal products
        $flashSale = FlashSale::updateOrCreate(
            ['slug' => 'daily-deals-campaign'],
            [
                'title' => 'Daily Deals & Flash Specials',
                'description' => 'Limited time discounts on top lifestyle drops.',
                'banner_image' => '/hero/he-2.avif',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(7),
                'is_active' => true,
            ]
        );

        FlashSaleItem::where('flash_sale_id', $flashSale->id)->delete();
        foreach ($dealProducts as $dealProd) {
            $discount = round((($dealProd->compare_price - $dealProd->price) / $dealProd->compare_price) * 100, 1);
            FlashSaleItem::create([
                'flash_sale_id' => $flashSale->id,
                'product_id' => $dealProd->id,
                'flash_price' => $dealProd->price,
                'discount_percentage' => $discount,
                'quantity_limit' => $dealProd->stock_quantity,
                'sold_count' => rand(5, 20),
            ]);
        }
    }
}
