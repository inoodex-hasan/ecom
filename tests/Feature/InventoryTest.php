<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->regularUser = User::factory()->create();
    }

    public function test_admin_can_view_inventory_page(): void
    {
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);
        Product::create([
            'name' => 'Drill Machine',
            'sku' => 'TOOL-DRL-01',
            'price' => 99.99,
            'stock_quantity' => 15,
            'low_stock_threshold' => 5,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.inventory.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Inventory/Index')
            ->has('products.data', 1)
            ->has('metrics')
            ->where('metrics.total_units', 15)
        );
    }

    public function test_admin_can_adjust_stock_for_standalone_product_and_creates_transaction(): void
    {
        $product = Product::create([
            'name' => 'Hammer Pro',
            'sku' => 'TOOL-HMR-01',
            'price' => 25.00,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.inventory.adjust'), [
            'product_id' => $product->id,
            'mode' => 'add',
            'quantity' => 5,
            'reason' => 'restock',
            'reference_number' => 'PO-2026-001',
            'notes' => 'Received from supplier',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 15,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'user_id' => $this->admin->id,
            'type' => 'in',
            'quantity_change' => 5,
            'previous_stock' => 10,
            'new_stock' => 15,
            'reason' => 'restock',
            'reference_number' => 'PO-2026-001',
        ]);
    }

    public function test_admin_can_adjust_stock_for_product_variant_and_syncs_parent_product(): void
    {
        $product = Product::create([
            'name' => 'Cotton T-Shirt',
            'sku' => 'TSHIRT-BASE',
            'price' => 29.99,
            'stock_quantity' => 20,
            'has_variants' => true,
            'low_stock_threshold' => 5,
            'status' => 'published',
        ]);

        $variant1 = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TSHIRT-S',
            'price' => 29.99,
            'stock_quantity' => 10,
            'option_values' => ['Size' => 'S'],
        ]);

        $variant2 = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TSHIRT-M',
            'price' => 29.99,
            'stock_quantity' => 10,
            'option_values' => ['Size' => 'M'],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.inventory.adjust'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant1->id,
            'mode' => 'remove',
            'quantity' => 4,
            'reason' => 'damaged',
            'notes' => 'Torn during shipping',
        ]);

        $response->assertRedirect();

        // Variant 1 stock should now be 6 (10 - 4)
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant1->id,
            'stock_quantity' => 6,
        ]);

        // Parent product stock should automatically sync to 16 (6 + 10)
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 16,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'product_variant_id' => $variant1->id,
            'type' => 'out',
            'quantity_change' => -4,
            'previous_stock' => 10,
            'new_stock' => 6,
            'reason' => 'damaged',
        ]);
    }

    public function test_admin_can_export_inventory_csv(): void
    {
        Product::create([
            'name' => 'Screwdriver Set',
            'sku' => 'TOOL-SCR-01',
            'price' => 19.99,
            'stock_quantity' => 30,
            'low_stock_threshold' => 10,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.inventory.export'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_unauthorized_user_cannot_access_inventory(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.inventory.index'));
        $response->assertStatus(403);
    }
}
