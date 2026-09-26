<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAndBusinessLogicTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    public function test_cancelling_order_replenishes_product_stock(): void
    {
        $product = Product::create([
            'name' => 'Smart Watch Pro',
            'sku' => 'SW-PRO-1',
            'price' => 250.00,
            'stock_quantity' => 10,
            'low_stock_threshold' => 3,
            'status' => 'published',
        ]);

        $customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-1',
            'customer_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'total' => 500.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => $product->price,
            'quantity' => 2,
            'total' => 500.00,
        ]);

        // Cancel the order
        $response = $this->actingAs($this->admin)->patch("/admin/orders/{$order->id}/status", [
            'status' => 'cancelled',
        ]);

        $response->assertRedirect();
        $this->assertEquals(12, $product->fresh()->stock_quantity);

        // Uncancel the order back to processing
        $response = $this->actingAs($this->admin)->patch("/admin/orders/{$order->id}/status", [
            'status' => 'processing',
        ]);

        $response->assertRedirect();
        $this->assertEquals(10, $product->fresh()->stock_quantity);
    }

    public function test_customer_lifetime_metrics_sync_accurately_on_payment_status_changes(): void
    {
        $customer = Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@example.com',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-2',
            'customer_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'unpaid',
            'total' => 350.00,
        ]);

        // Mark as paid
        $this->actingAs($this->admin)->patch("/admin/orders/{$order->id}/payment-status", [
            'payment_status' => 'paid',
        ]);

        $customer->refresh();
        $this->assertEquals(350.00, $customer->total_spent);
        $this->assertEquals(1, $customer->total_orders);

        // Mark as refunded
        $this->actingAs($this->admin)->patch("/admin/orders/{$order->id}/payment-status", [
            'payment_status' => 'refunded',
        ]);

        $customer->refresh();
        $this->assertEquals(0.00, $customer->total_spent);
    }

    public function test_cannot_delete_product_with_active_orders(): void
    {
        $product = Product::create([
            'name' => 'Mechanical Keyboard',
            'sku' => 'KB-MECH-1',
            'price' => 120.00,
            'stock_quantity' => 5,
            'low_stock_threshold' => 2,
            'status' => 'published',
        ]);

        $customer = Customer::create([
            'first_name' => 'Bob',
            'last_name' => 'Builder',
            'email' => 'bob@example.com',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-3',
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total' => 120.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => $product->price,
            'quantity' => 1,
            'total' => 120.00,
        ]);

        // Attempt deletion
        $response = $this->actingAs($this->admin)->delete("/admin/products/{$product->id}");
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
