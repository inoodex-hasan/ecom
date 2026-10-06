<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    public function test_authorized_user_can_view_orders_index(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Index')
            ->has('orders.data')
            ->has('statusCounts')
        );
    }

    public function test_unauthorized_user_cannot_view_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get(route('admin.orders.index'));

        $response->assertStatus(403);
    }

    public function test_orders_index_filters_by_status(): void
    {
        Order::factory()->create(['status' => 'pending']);
        Order::factory()->create(['status' => 'delivered']);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'delivered']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Index')
            ->where('orders.total', 1)
            ->where('orders.data.0.status', 'delivered')
        );
    }

    public function test_authorized_user_can_view_order_details(): void
    {
        $order = Order::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Show')
            ->where('order.id', $order->id)
        );
    }

    public function test_authorized_user_can_update_payment_status(): void
    {
        $order = Order::factory()->create([
            'payment_status' => 'unpaid',
            'paid_at' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-payment-status', $order), [
                'payment_status' => 'paid',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_authorized_user_can_view_order_invoice(): void
    {
        $order = Order::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.orders.invoice', $order));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Invoice')
            ->where('order.id', $order->id)
        );
    }

    public function test_authorized_user_can_export_orders(): void
    {
        Order::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.orders.export', ['format' => 'csv']));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition') ?? '', 'orders-export'));
    }
}
