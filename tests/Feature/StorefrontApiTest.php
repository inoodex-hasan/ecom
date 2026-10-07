<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed sample category & products
        $this->category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Pro Wireless Headphones',
            'slug' => 'pro-wireless-headphones',
            'sku' => 'PWH-001',
            'price' => 199.99,
            'stock_quantity' => 25,
            'status' => 'published',
            'category_id' => $this->category->id,
            'is_hot' => true,
        ]);
    }

    public function test_api_ping(): void
    {
        $response = $this->getJson('/api/v1/ping');
        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }

    public function test_api_settings(): void
    {
        $response = $this->getJson('/api/v1/settings');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['store_name', 'currency_symbol', 'contact_email']]);
    }

    public function test_api_home_feed(): void
    {
        Banner::create([
            'title' => 'Big Mega Sale',
            'image_url' => 'https://example.com/banner.jpg',
            'is_active' => true,
            'order' => 1,
        ]);

        $response = $this->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'banners',
                    'categories',
                    'active_campaigns',
                    'hot_products',
                    'featured_products',
                ],
            ]);
    }

    public function test_api_products_catalog(): void
    {
        $response = $this->getJson('/api/v1/products?category=electronics');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Pro Wireless Headphones');
    }

    public function test_api_single_product_detail(): void
    {
        $response = $this->getJson('/api/v1/products/pro-wireless-headphones');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product.name', 'Pro Wireless Headphones');
    }

    public function test_api_categories_list(): void
    {
        $response = $this->getJson('/api/v1/categories');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_api_campaigns_list(): void
    {
        $sale = FlashSale::create([
            'title' => 'Weekend Super Deals',
            'slug' => 'weekend-super-deals',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        FlashSaleItem::create([
            'flash_sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'flash_price' => 149.99,
        ]);

        $response = $this->getJson('/api/v1/campaigns');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_api_coupon_validation(): void
    {
        Coupon::create([
            'code' => 'SAVE20',
            'type' => 'fixed_cart',
            'value' => 20,
            'min_spend' => 50,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'SAVE20',
            'subtotal' => 100,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.discount_amount', 20);
    }

    public function test_api_order_checkout_flow(): void
    {
        $payload = [
            'first_name' => 'Hasan',
            'last_name' => 'Ali',
            'email' => 'hasan@example.com',
            'phone' => '01712345678',
            'address_line_1' => 'House 12, Road 4, Banani',
            'city' => 'Dhaka',
            'payment_method' => 'cod',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $initialStock = $this->product->stock_quantity;

        $response = $this->postJson('/api/v1/orders', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['order_number', 'total', 'status']]);

        // Stock should be decremented
        $this->assertEquals($initialStock - 2, $this->product->fresh()->stock_quantity);

        // Order tracking test
        $orderNumber = $response->json('data.order_number');
        $trackResponse = $this->getJson("/api/v1/orders/track/{$orderNumber}");
        $trackResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_number', $orderNumber);
    }

    public function test_api_guest_checkout_with_simplified_parameters(): void
    {
        $payload = [
            'name' => 'Tanvir Ahmed',
            'phone' => '01819001122',
            'address' => 'House 42, Road 11, Banani',
            'area' => 'Banani',
            'city' => 'Dhaka',
            'postcode' => '1213',
            'delivery' => 'home',
            'payment' => 'bkash',
            'wallet' => '01819001122',
            'trxid' => '9H8B7C6D5E',
            'delivery_charge' => 60,
            'items' => [
                [
                    'id' => (string) $this->product->id,
                    'qty' => 1,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/orders', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['order_number', 'total', 'status']]);

        $orderNumber = $response->json('data.order_number');
        $order = Order::where('order_number', $orderNumber)->first();

        $this->assertNotNull($order);
        $this->assertEquals('bkash', $order->payment_method);
        $this->assertEquals('9H8B7C6D5E', $order->advance_transaction_id);
        $this->assertEquals('Tanvir Ahmed', $order->shipping_address['name']);
        $this->assertEquals('House 42, Road 11, Banani', $order->shipping_address['address_line_1']);
        $this->assertEquals('Banani', $order->shipping_address['area']);
        $this->assertEquals(60, $order->shipping_cost);
        $this->assertEquals('01819001122', $order->shipping_address['wallet_number']);
        $this->assertEquals('9H8B7C6D5E', $order->shipping_address['trx_id']);
    }

    public function test_api_pages_and_about_us(): void
    {
        Page::create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'content' => '<p>About our company story.</p>',
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/v1/pages/about-us');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'About Us');
    }

    public function test_api_blogs(): void
    {
        BlogPost::create([
            'title' => 'Top 5 Summer Trends',
            'slug' => 'top-5-summer-trends',
            'content' => '<p>Article body</p>',
            'category' => 'Trends',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/blogs');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $singleResponse = $this->getJson('/api/v1/blogs/top-5-summer-trends');
        $singleResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.post.title', 'Top 5 Summer Trends');
    }

    public function test_api_contact_us_submission(): void
    {
        $payload = [
            'name' => 'Sarah Connor',
            'email' => 'sarah@example.com',
            'phone' => '01899999999',
            'subject' => 'Product question',
            'message' => 'I would like to inquire about bulk ordering discounts.',
        ];

        $response = $this->postJson('/api/v1/contact', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('inquiries', [
            'email' => 'sarah@example.com',
            'subject' => 'Product question',
            'status' => 'unread',
        ]);
    }
}
