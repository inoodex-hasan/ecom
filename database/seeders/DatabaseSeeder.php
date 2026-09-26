<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Admin & Staff Users
        User::firstOrCreate(
            ['email' => 'hello@inoodex.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('hello@inoodex.com'),
                'role' => 'admin',
                'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&auto=format&fit=crop&q=80',
            ]
        );

        // 2. Seed Settings
        $defaultSettings = [
            ['key' => 'store_name', 'value' => 'Apex Store', 'type' => 'string', 'group' => 'general'],
            ['key' => 'store_email', 'value' => 'support@apexstore.io', 'type' => 'string', 'group' => 'general'],
            ['key' => 'store_phone', 'value' => '+1 (555) 234-5678', 'type' => 'string', 'group' => 'general'],
            ['key' => 'currency_symbol', 'value' => '$', 'type' => 'string', 'group' => 'localization'],
            ['key' => 'currency_code', 'value' => 'USD', 'type' => 'string', 'group' => 'localization'],
            ['key' => 'tax_rate_percentage', 'value' => '8.25', 'type' => 'float', 'group' => 'tax'],
            ['key' => 'flat_shipping_rate', 'value' => '12.50', 'type' => 'float', 'group' => 'shipping'],
            ['key' => 'free_shipping_threshold', 'value' => '150.00', 'type' => 'float', 'group' => 'shipping'],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // 3. Seed Categories
        $categoriesData = [
            ['name' => 'Electronics', 'icon' => 'Tv', 'description' => 'Cutting-edge consumer electronic gadgets'],
            ['name' => 'Smartphones & Tablets', 'icon' => 'Smartphone', 'description' => 'Latest flagship mobile devices'],
            ['name' => 'Laptops & Computers', 'icon' => 'Laptop', 'description' => 'High-performance laptops, workstations and accessories'],
            ['name' => 'Audio & Headphones', 'icon' => 'Headphones', 'description' => 'Studio quality headphones, speakers, and earbuds'],
            ['name' => 'Wearables & Smartwatches', 'icon' => 'Watch', 'description' => 'Fitness trackers, sports bands, and smartwatch gear'],
            ['name' => 'Accessories', 'icon' => 'Cable', 'description' => 'Chargers, cables, stands, and protective cases'],
        ];

        $categories = [];
        foreach ($categoriesData as $index => $cat) {
            $categories[$cat['name']] = Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'icon' => $cat['icon'],
                    'description' => $cat['description'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        // 4. Seed Brands
        $brandsData = [
            ['name' => 'Apple', 'website' => 'https://apple.com'],
            ['name' => 'Samsung', 'website' => 'https://samsung.com'],
            ['name' => 'Sony', 'website' => 'https://sony.com'],
            ['name' => 'Dell', 'website' => 'https://dell.com'],
            ['name' => 'Logitech', 'website' => 'https://logitech.com'],
            ['name' => 'Bose', 'website' => 'https://bose.com'],
        ];

        $brands = [];
        foreach ($brandsData as $brand) {
            $brands[$brand['name']] = Brand::firstOrCreate(
                ['slug' => Str::slug($brand['name'])],
                [
                    'name' => $brand['name'],
                    'website' => $brand['website'],
                    'is_active' => true,
                ]
            );
        }

        // 5. Seed Products
        $productsData = [
            [
                'name' => 'MacBook Pro 16" M3 Max',
                'category' => 'Laptops & Computers',
                'brand' => 'Apple',
                'sku' => 'APL-MBP-16-M3',
                'price' => 3499.00,
                'compare_price' => 3799.00,
                'cost_price' => 2800.00,
                'stock_quantity' => 14,
                'low_stock_threshold' => 5,
                'status' => 'published',
                'is_featured' => true,
                'primary_image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'The ultimate pro laptop with M3 Max 16-core CPU and 40-core GPU.',
                'description' => 'Engineered for extreme performance, featuring a Liquid Retina XDR display, up to 22 hours of battery life, and high-speed unified memory.',
            ],
            [
                'name' => 'Sony WH-1000XM5 Wireless Headphones',
                'category' => 'Audio & Headphones',
                'brand' => 'Sony',
                'sku' => 'SNY-WH1000-XM5',
                'price' => 399.99,
                'compare_price' => 449.99,
                'cost_price' => 260.00,
                'stock_quantity' => 28,
                'low_stock_threshold' => 10,
                'status' => 'published',
                'is_featured' => true,
                'primary_image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'Industry-leading noise canceling with two processors and 8 microphones.',
                'description' => 'Magnificent sound quality, crystal-clear hands-free calling, up to 30 hours of battery life with ultra-fast charging.',
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra 512GB',
                'category' => 'Smartphones & Tablets',
                'brand' => 'Samsung',
                'sku' => 'SAM-GS24-ULTRA',
                'price' => 1299.99,
                'compare_price' => 1419.99,
                'cost_price' => 950.00,
                'stock_quantity' => 4, // Low stock!
                'low_stock_threshold' => 6,
                'status' => 'published',
                'is_featured' => true,
                'primary_image' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'Galaxy AI is here. Titanium frame and 200MP camera powerhouse.',
                'description' => 'Unleash new levels of creativity and productivity with built-in S Pen, dynamic AMOLED 2X flat display, and Qualcomm Snapdragon 8 Gen 3.',
            ],
            [
                'name' => 'Dell UltraSharp 32" 4K USB-C Hub Monitor',
                'category' => 'Laptops & Computers',
                'brand' => 'Dell',
                'sku' => 'DEL-U3223QE',
                'price' => 849.00,
                'compare_price' => 999.00,
                'cost_price' => 600.00,
                'stock_quantity' => 19,
                'low_stock_threshold' => 5,
                'status' => 'published',
                'is_featured' => false,
                'primary_image' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'IPS Black technology with 2000:1 contrast ratio and 90W power delivery.',
                'description' => 'Brilliant color depth, 4K resolution clarity, integrated RJ45 ethernet and multiple USB-C hub connectivity.',
            ],
            [
                'name' => 'Logitech MX Master 3S Wireless Mouse',
                'category' => 'Accessories',
                'brand' => 'Logitech',
                'sku' => 'LOG-MX-M3S',
                'price' => 99.99,
                'compare_price' => null,
                'cost_price' => 55.00,
                'stock_quantity' => 45,
                'low_stock_threshold' => 8,
                'status' => 'published',
                'is_featured' => true,
                'primary_image' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'Quiet clicks and 8K DPI any-surface tracking precision.',
                'description' => 'MagSpeed electromagnetic scrolling is 90% faster and 87% more precise. Ergonomic silhouette crafted for palm comfort.',
            ],
            [
                'name' => 'Apple Watch Ultra 2 GPS + Cellular',
                'category' => 'Wearables & Smartwatches',
                'brand' => 'Apple',
                'sku' => 'APL-AW-ULTRA2',
                'price' => 799.00,
                'compare_price' => 849.00,
                'cost_price' => 580.00,
                'stock_quantity' => 2, // Low stock!
                'low_stock_threshold' => 5,
                'status' => 'published',
                'is_featured' => true,
                'primary_image' => 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'Rugged titanium 49mm case with precision dual-frequency GPS.',
                'description' => 'Built for endurance athletes and outdoor adventurers. Up to 36 hours of battery life and bright 3000-nit display.',
            ],
            [
                'name' => 'Bose QuietComfort Ultra Earbuds',
                'category' => 'Audio & Headphones',
                'brand' => 'Bose',
                'sku' => 'BOS-QCU-EAR',
                'price' => 299.00,
                'compare_price' => 329.00,
                'cost_price' => 190.00,
                'stock_quantity' => 22,
                'low_stock_threshold' => 5,
                'status' => 'published',
                'is_featured' => false,
                'primary_image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'Breakthrough spatialized audio with world-class noise cancellation.',
                'description' => 'CustomTune technology personalizes audio to your ears. Immersive Audio breaks acoustic boundaries.',
            ],
            [
                'name' => 'Studio Display 27" 5K Retina (Draft)',
                'category' => 'Electronics',
                'brand' => 'Apple',
                'sku' => 'APL-STD-DISP',
                'price' => 1599.00,
                'compare_price' => null,
                'cost_price' => 1100.00,
                'stock_quantity' => 0,
                'low_stock_threshold' => 3,
                'status' => 'draft',
                'is_featured' => false,
                'primary_image' => 'https://images.unsplash.com/photo-1585792180666-f7347c490ee2?w=600&auto=format&fit=crop&q=80',
                'short_description' => 'Immersive 27-inch 5K Retina display with 12MP camera with Center Stage.',
                'description' => 'Draft product awaiting next inventory batch arrival.',
            ],
        ];

        $createdProducts = [];
        foreach ($productsData as $prod) {
            $catId = $categories[$prod['category']]->id ?? null;
            $brandId = $brands[$prod['brand']]->id ?? null;

            $product = Product::updateOrCreate(
                ['sku' => $prod['sku']],
                [
                    'category_id' => $catId,
                    'brand_id' => $brandId,
                    'name' => $prod['name'],
                    'slug' => Str::slug($prod['name']),
                    'price' => $prod['price'],
                    'compare_price' => $prod['compare_price'],
                    'cost_price' => $prod['cost_price'],
                    'stock_quantity' => $prod['stock_quantity'],
                    'low_stock_threshold' => $prod['low_stock_threshold'],
                    'status' => $prod['status'],
                    'is_featured' => $prod['is_featured'],
                    'primary_image' => $prod['primary_image'],
                    'short_description' => $prod['short_description'],
                    'description' => $prod['description'],
                ]
            );

            ProductImage::firstOrCreate(
                ['product_id' => $product->id, 'image_path' => $prod['primary_image']],
                ['is_primary' => true, 'sort_order' => 1]
            );

            $createdProducts[] = $product;
        }

        // 6. Seed Customers
        $customersData = [
            [
                'first_name' => 'Alexander',
                'last_name' => 'Wright',
                'email' => 'alexander.wright@example.com',
                'phone' => '+1 (555) 392-1049',
                'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120&auto=format&fit=crop&q=80',
                'address_line_1' => '742 Evergreen Terrace',
                'city' => 'Springfield',
                'state' => 'OR',
                'postal_code' => '97477',
                'country' => 'United States',
                'status' => 'active',
            ],
            [
                'first_name' => 'Elena',
                'last_name' => 'Rostova',
                'email' => 'elena.rostova@example.com',
                'phone' => '+1 (555) 782-9901',
                'avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=120&auto=format&fit=crop&q=80',
                'address_line_1' => '450 Sunset Boulevard, Suite 300',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'postal_code' => '90028',
                'country' => 'United States',
                'status' => 'active',
            ],
            [
                'first_name' => 'Marcus',
                'last_name' => 'Chen',
                'email' => 'marcus.chen@example.com',
                'phone' => '+1 (555) 412-8822',
                'avatar' => 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=120&auto=format&fit=crop&q=80',
                'address_line_1' => '120 Innovation Way',
                'city' => 'Austin',
                'state' => 'TX',
                'postal_code' => '78701',
                'country' => 'United States',
                'status' => 'active',
            ],
            [
                'first_name' => 'Sophia',
                'last_name' => 'Taylor',
                'email' => 'sophia.taylor@example.com',
                'phone' => '+1 (555) 671-3490',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80',
                'address_line_1' => '88 King Street',
                'city' => 'Seattle',
                'state' => 'WA',
                'postal_code' => '98104',
                'country' => 'United States',
                'status' => 'active',
            ],
            [
                'first_name' => 'Liam',
                'last_name' => 'Dubois',
                'email' => 'liam.dubois@example.com',
                'phone' => '+1 (555) 902-3112',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80',
                'address_line_1' => '312 Beacon Hill Rd',
                'city' => 'Boston',
                'state' => 'MA',
                'postal_code' => '02108',
                'country' => 'United States',
                'status' => 'active',
            ],
        ];

        $customers = [];
        foreach ($customersData as $cust) {
            $customers[] = Customer::updateOrCreate(['email' => $cust['email']], $cust);
        }

        // 7. Seed Orders with Line Items & Timeline Dates
        $orderScenarios = [
            [
                'customer_idx' => 0,
                'status' => 'delivered',
                'payment_status' => 'paid',
                'payment_method' => 'credit_card',
                'created_at' => now()->subDays(6),
                'paid_at' => now()->subDays(6),
                'shipped_at' => now()->subDays(4),
                'delivered_at' => now()->subDays(2),
                'items' => [
                    ['sku' => 'APL-MBP-16-M3', 'qty' => 1],
                    ['sku' => 'LOG-MX-M3S', 'qty' => 1],
                ],
            ],
            [
                'customer_idx' => 1,
                'status' => 'shipped',
                'payment_status' => 'paid',
                'payment_method' => 'paypal',
                'created_at' => now()->subDays(2),
                'paid_at' => now()->subDays(2),
                'shipped_at' => now()->subDay(),
                'delivered_at' => null,
                'items' => [
                    ['sku' => 'SNY-WH1000-XM5', 'qty' => 1],
                    ['sku' => 'BOS-QCU-EAR', 'qty' => 1],
                ],
            ],
            [
                'customer_idx' => 2,
                'status' => 'processing',
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'created_at' => now()->subHours(10),
                'paid_at' => now()->subHours(10),
                'shipped_at' => null,
                'delivered_at' => null,
                'items' => [
                    ['sku' => 'SAM-GS24-ULTRA', 'qty' => 1],
                ],
            ],
            [
                'customer_idx' => 3,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => 'credit_card',
                'created_at' => now()->subHours(3),
                'paid_at' => null,
                'shipped_at' => null,
                'delivered_at' => null,
                'items' => [
                    ['sku' => 'APL-AW-ULTRA2', 'qty' => 1],
                    ['sku' => 'LOG-MX-M3S', 'qty' => 2],
                ],
            ],
            [
                'customer_idx' => 4,
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'payment_method' => 'credit_card',
                'created_at' => now()->subDays(8),
                'paid_at' => now()->subDays(8),
                'shipped_at' => null,
                'delivered_at' => null,
                'items' => [
                    ['sku' => 'DEL-U3223QE', 'qty' => 1],
                ],
            ],
            [
                'customer_idx' => 0,
                'status' => 'delivered',
                'payment_status' => 'paid',
                'payment_method' => 'credit_card',
                'created_at' => now()->subDays(14),
                'paid_at' => now()->subDays(14),
                'shipped_at' => now()->subDays(12),
                'delivered_at' => now()->subDays(10),
                'items' => [
                    ['sku' => 'DEL-U3223QE', 'qty' => 2],
                ],
            ],
        ];

        foreach ($orderScenarios as $idx => $scenario) {
            $customer = $customers[$scenario['customer_idx']];
            $orderNum = 'ORD-2026-'.str_pad((string) (1001 + $idx), 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $itemsToCreate = [];

            foreach ($scenario['items'] as $itemData) {
                $p = Product::where('sku', $itemData['sku'])->first();
                if ($p) {
                    $itemTotal = $p->price * $itemData['qty'];
                    $subtotal += $itemTotal;
                    $itemsToCreate[] = [
                        'product_id' => $p->id,
                        'product_name' => $p->name,
                        'sku' => $p->sku,
                        'image' => $p->primary_image,
                        'unit_price' => $p->price,
                        'quantity' => $itemData['qty'],
                        'total' => $itemTotal,
                    ];
                }
            }

            $tax = round($subtotal * 0.0825, 2);
            $shippingCost = $subtotal > 150 ? 0.00 : 12.50;
            $total = $subtotal + $tax + $shippingCost;

            $shippingAddress = [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address_line_1,
                'city' => $customer->city,
                'state' => $customer->state,
                'postal_code' => $customer->postal_code,
                'country' => $customer->country,
            ];

            $order = Order::updateOrCreate(
                ['order_number' => $orderNum],
                [
                    'customer_id' => $customer->id,
                    'status' => $scenario['status'],
                    'payment_status' => $scenario['payment_status'],
                    'payment_method' => $scenario['payment_method'],
                    'subtotal' => $subtotal,
                    'discount' => 0.00,
                    'tax' => $tax,
                    'shipping_cost' => $shippingCost,
                    'total' => $total,
                    'shipping_address' => $shippingAddress,
                    'billing_address' => $shippingAddress,
                    'paid_at' => $scenario['paid_at'],
                    'shipped_at' => $scenario['shipped_at'],
                    'delivered_at' => $scenario['delivered_at'],
                    'created_at' => $scenario['created_at'],
                    'updated_at' => $scenario['created_at'],
                ]
            );

            // Re-sync order items
            $order->items()->delete();
            foreach ($itemsToCreate as $item) {
                $order->items()->create($item);
            }

            // Update customer aggregates
            if ($order->payment_status === 'paid') {
                $customer->increment('total_spent', $order->total);
                $customer->increment('total_orders', 1);
            }
        }

        // 8. Seed Roles & Permissions
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
