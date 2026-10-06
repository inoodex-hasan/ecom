<?php

namespace Tests\Feature;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->regularUser = User::factory()->create();

        $this->product = Product::create([
            'name' => 'High-End Headphones',
            'sku' => 'TECH-HDPH-01',
            'price' => 199.99,
            'stock_quantity' => 50,
            'low_stock_threshold' => 10,
            'status' => 'published',
            'is_hot' => true,
            'is_trending' => true,
            'is_new_arrival' => false,
            'badge_label' => 'Staff Pick',
        ]);
    }

    public function test_admin_can_view_flash_sales_index(): void
    {
        $sale = FlashSale::create([
            'title' => 'Midnight Madness',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(6),
            'is_active' => true,
        ]);

        FlashSaleItem::create([
            'flash_sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'flash_price' => 99.99,
            'quantity_limit' => 20,
            'quantity_sold' => 5,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.campaigns.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Campaigns/Index')
            ->has('flashSales', 1)
            ->has('metrics')
            ->where('metrics.total_campaigns', 1)
            ->where('metrics.active_running', 1)
        );
    }

    public function test_legacy_flash_sales_route_redirects_to_campaigns(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.flash-sales.index'));
        $response->assertRedirect(route('admin.campaigns.index'));
    }

    public function test_unauthorized_user_cannot_access_flash_sales(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.campaigns.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_flash_sale_with_items(): void
    {
        $payload = [
            'title' => 'Super 10.10 Flash Carnival',
            'description' => 'Huge discounts on electronics',
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'flash_price' => 89.99,
                    'quantity_limit' => 30,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->from(route('admin.campaigns.index'))
            ->post(route('admin.campaigns.store'), $payload);

        $response->assertRedirect(route('admin.campaigns.index'));
        $this->assertDatabaseHas('flash_sales', [
            'title' => 'Super 10.10 Flash Carnival',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('flash_sale_items', [
            'product_id' => $this->product->id,
            'flash_price' => 89.99,
            'quantity_limit' => 30,
        ]);
    }

    public function test_admin_can_update_flash_sale(): void
    {
        $sale = FlashSale::create([
            'title' => 'Original Flash Campaign',
            'starts_at' => now(),
            'ends_at' => now()->addHours(12),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.campaigns.index'))
            ->put(route('admin.campaigns.update', $sale), [
                'title' => 'Renamed Flash Campaign',
                'description' => 'Updated terms',
                'starts_at' => now()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addHours(24)->format('Y-m-d H:i:s'),
                'is_active' => false,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'flash_price' => 79.99,
                        'quantity_limit' => 15,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.campaigns.index'));
        $this->assertDatabaseHas('flash_sales', [
            'id' => $sale->id,
            'title' => 'Renamed Flash Campaign',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('flash_sale_items', [
            'flash_sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'flash_price' => 79.99,
        ]);
    }

    public function test_admin_can_toggle_flash_sale_status(): void
    {
        $sale = FlashSale::create([
            'title' => 'Toggle Flash',
            'starts_at' => now(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.campaigns.index'))
            ->patch(route('admin.campaigns.toggle-status', $sale));

        $response->assertRedirect(route('admin.campaigns.index'));
        $this->assertDatabaseHas('flash_sales', [
            'id' => $sale->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_flash_sale_and_cascades_items(): void
    {
        $sale = FlashSale::create([
            'title' => 'To Be Removed',
            'starts_at' => now(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        FlashSaleItem::create([
            'flash_sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'flash_price' => 149.99,
            'quantity_limit' => 10,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.campaigns.index'))
            ->delete(route('admin.campaigns.destroy', $sale));

        $response->assertRedirect(route('admin.campaigns.index'));
        $this->assertDatabaseMissing('flash_sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('flash_sale_items', ['flash_sale_id' => $sale->id]);
    }

    public function test_product_merchandising_badges_and_scopes(): void
    {
        $hotProduct = Product::create([
            'name' => 'Hot Product',
            'sku' => 'HOT-01',
            'price' => 10,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'is_hot' => true,
            'is_trending' => false,
            'is_new_arrival' => false,
            'status' => 'published',
        ]);

        $trendingProduct = Product::create([
            'name' => 'Trending Product',
            'sku' => 'TREND-01',
            'price' => 20,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'is_hot' => false,
            'is_trending' => true,
            'is_new_arrival' => false,
            'status' => 'published',
        ]);

        $newProduct = Product::create([
            'name' => 'New Product',
            'sku' => 'NEW-01',
            'price' => 30,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'is_hot' => false,
            'is_trending' => false,
            'is_new_arrival' => true,
            'badge_label' => 'Just Dropped',
            'status' => 'published',
        ]);

        $this->assertTrue(Product::hot()->where('id', $hotProduct->id)->exists());
        $this->assertFalse(Product::hot()->where('id', $trendingProduct->id)->exists());

        $this->assertTrue(Product::trending()->where('id', $trendingProduct->id)->exists());
        $this->assertFalse(Product::trending()->where('id', $newProduct->id)->exists());

        $this->assertTrue(Product::newArrival()->where('id', $newProduct->id)->exists());
        $this->assertEquals('Just Dropped', $newProduct->badge_label);
    }
}
