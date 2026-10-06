<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_authorized_user_can_view_dashboard_with_metrics_and_charts(): void
    {
        Order::factory()->create([
            'payment_status' => 'paid',
            'status' => 'delivered',
            'total' => 1500,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('metrics')
            ->where('metrics.total_revenue', 1500)
            ->where('metrics.total_orders', 1)
            ->has('salesChart')
            ->has('salesChart.categories')
            ->has('salesChart.revenue')
            ->has('statusDistribution')
            ->has('recentOrders')
            ->has('lowStockProducts')
        );
    }

    public function test_dashboard_reflects_low_stock_products(): void
    {
        Product::create([
            'name' => 'Low Stock Item',
            'sku' => 'LOW-01',
            'price' => 100,
            'stock_quantity' => 2,
            'low_stock_threshold' => 5,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('metrics.low_stock_count', 1)
            ->has('lowStockProducts', 1)
        );
    }

    public function test_unauthorized_user_cannot_access_dashboard(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }
}
