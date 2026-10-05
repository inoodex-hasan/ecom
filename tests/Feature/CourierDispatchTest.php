<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\CourierService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected CourierService $courierService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->courierService = app(CourierService::class);
    }

    public function test_calculate_cod_amount_deducts_advance_fee_and_handles_prepaid(): void
    {
        $customer = Customer::create([
            'first_name' => 'Tanvir',
            'last_name' => 'Ahmed',
            'email' => 'tanvir@example.com',
            'phone' => '01712345678',
        ]);

        // Scenario 1: Standard COD without advance payment -> full total to collect
        $codOrder = Order::create([
            'order_number' => 'ORD-COD-1',
            'customer_id' => $customer->id,
            'subtotal' => 2000,
            'shipping_cost' => 100,
            'total' => 2100,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);
        $this->assertEquals(2100, $this->courierService->calculateCodAmount($codOrder));

        // Scenario 2: COD with Advance Delivery fee paid -> Net COD (total - advance)
        $advanceOrder = Order::create([
            'order_number' => 'ORD-ADV-1',
            'customer_id' => $customer->id,
            'subtotal' => 3000,
            'shipping_cost' => 150,
            'total' => 3150,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'advance_payment_status' => 'paid',
            'advance_delivery_charge' => 150,
            'status' => 'processing',
        ]);
        $this->assertEquals(3000, $this->courierService->calculateCodAmount($advanceOrder));

        // Scenario 3: Fully paid order -> 0 COD to collect
        $paidOrder = Order::create([
            'order_number' => 'ORD-PAID-1',
            'customer_id' => $customer->id,
            'subtotal' => 1500,
            'shipping_cost' => 100,
            'total' => 1600,
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);
        $this->assertEquals(0, $this->courierService->calculateCodAmount($paidOrder));
    }

    public function test_admin_can_dispatch_order_to_steadfast_courier(): void
    {
        $customer = Customer::create([
            'first_name' => 'Rahim',
            'last_name' => 'Uddin',
            'email' => 'rahim@example.com',
            'phone' => '01711223344',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-STF-101',
            'customer_id' => $customer->id,
            'subtotal' => 2500,
            'shipping_cost' => 100,
            'total' => 2600,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'status' => 'processing',
            'shipping_address' => [
                'name' => 'Rahim Uddin',
                'phone' => '01711223344',
                'address' => 'House 12, Road 4, Dhanmondi',
                'city' => 'Dhaka',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.courier-dispatch', $order->id), [
            'courier_provider' => 'steadfast',
            'note' => 'Handle with care',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('steadfast', $order->courier_provider);
        $this->assertNotNull($order->courier_consignment_id);
        $this->assertNotNull($order->courier_tracking_code);
        $this->assertEquals(2600, $order->courier_cod_amount);
        $this->assertEquals('in_review', $order->courier_status);
        $this->assertNotNull($order->courier_dispatched_at);
    }

    public function test_admin_can_sync_courier_status(): void
    {
        $customer = Customer::create([
            'first_name' => 'Karim',
            'last_name' => 'Chowdhury',
            'email' => 'karim@example.com',
            'phone' => '01811223344',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-STF-202',
            'customer_id' => $customer->id,
            'subtotal' => 1200,
            'shipping_cost' => 60,
            'total' => 1260,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'status' => 'shipped',
            'courier_provider' => 'steadfast',
            'courier_consignment_id' => 'STF-SIM-TEST999',
            'courier_tracking_code' => 'TRK-TEST999',
            'courier_status' => 'in_transit',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.courier-sync', $order->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_view_shipping_manifest_label(): void
    {
        $customer = Customer::create([
            'first_name' => 'Anisur',
            'last_name' => 'Rahman',
            'email' => 'anis@example.com',
            'phone' => '01911223344',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-LBL-303',
            'customer_id' => $customer->id,
            'subtotal' => 4500,
            'shipping_cost' => 100,
            'total' => 4600,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'status' => 'processing',
            'courier_provider' => 'steadfast',
            'courier_consignment_id' => 'STF-SIM-303',
            'courier_tracking_code' => 'TRK-303',
            'courier_cod_amount' => 4600,
            'shipping_address' => [
                'name' => 'Anisur Rahman',
                'phone' => '01911223344',
                'address' => 'Flat 4B, Sector 3, Uttara',
                'city' => 'Dhaka',
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.courier-label', $order->id));
        $response->assertOk();
    }
}
