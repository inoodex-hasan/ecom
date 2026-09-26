<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminPerformanceTest extends TestCase
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

    public function test_site_settings_are_cached_and_invalidated_on_update(): void
    {
        Setting::create(['key' => 'store_name', 'value' => 'Initial Store']);

        // First request populates cache
        $this->actingAs($this->admin)->get('/admin/dashboard');
        $this->assertTrue(Cache::has('site_settings'));
        $this->assertEquals('Initial Store', Cache::get('site_settings')['store_name']);

        // Updating settings invalidates the cache
        $this->actingAs($this->admin)->post('/admin/settings', [
            'settings' => [
                'store_name' => 'Updated Brand Store',
            ],
        ]);

        $this->assertFalse(Cache::has('site_settings'));

        // Next request re-populates cache with new value
        $this->actingAs($this->admin)->get('/admin/dashboard');
        $this->assertTrue(Cache::has('site_settings'));
        $this->assertEquals('Updated Brand Store', Cache::get('site_settings')['store_name']);
    }

    public function test_order_status_counts_aggregation_is_accurate(): void
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        Order::create([
            'order_number' => 'ORD-101',
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total' => 100.00,
        ]);

        Order::create([
            'order_number' => 'ORD-102',
            'customer_id' => $customer->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'total' => 200.00,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/orders');
        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Orders/Index')
            ->where('statusCounts.all', 2)
            ->where('statusCounts.pending', 1)
            ->where('statusCounts.delivered', 1)
            ->where('statusCounts.processing', 0)
        );
    }

    public function test_dashboard_metrics_and_charts_work_efficiently(): void
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
        ]);

        Order::create([
            'order_number' => 'ORD-201',
            'customer_id' => $customer->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'total' => 150.00,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/dashboard');
        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('metrics')
            ->has('salesChart.categories', 12)
            ->has('salesChart.revenue', 12)
            ->has('statusDistribution')
            ->where('statusDistribution.delivered', 1)
            ->where('statusDistribution.pending', 0)
        );
    }
}
