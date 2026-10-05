<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FraudBlacklist;
use App\Models\Order;
use App\Models\User;
use App\Services\FraudCheckService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraudCheckTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected FraudCheckService $fraudService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->fraudService = app(FraudCheckService::class);
    }

    public function test_bangladeshi_phone_normalization_and_operator_detection(): void
    {
        $gp = $this->fraudService->validateBdPhone('+8801712345678');
        $this->assertEquals('01712345678', $gp['normalized']);
        $this->assertEquals('Grameenphone', $gp['operator']['name']);

        $bl = $this->fraudService->validateBdPhone('8801912345678');
        $this->assertEquals('01912345678', $bl['normalized']);
        $this->assertEquals('Banglalink', $bl['operator']['name']);

        $robi = $this->fraudService->validateBdPhone('01812345678');
        $this->assertEquals('01812345678', $robi['normalized']);
        $this->assertEquals('Robi', $robi['operator']['name']);

        $airtel = $this->fraudService->validateBdPhone('01612345678');
        $this->assertEquals('Airtel', $airtel['operator']['name']);

        $teletalk = $this->fraudService->validateBdPhone('01512345678');
        $this->assertEquals('Teletalk', $teletalk['operator']['name']);
    }

    public function test_fake_dummy_phone_number_detection(): void
    {
        $fakeResult = $this->fraudService->validateBdPhone('01700000000');
        $this->assertFalse($fakeResult['is_valid']);
        $this->assertStringContainsString('Repetitive', $fakeResult['reason']);
        $this->assertGreaterThan(0, $fakeResult['risk_points']);

        $invalidResult = $this->fraudService->validateBdPhone('12345');
        $this->assertFalse($invalidResult['is_valid']);
    }

    public function test_order_advance_delivery_charge_calculation_for_dhaka_and_outside(): void
    {
        $feeDhaka = $this->fraudService->getRecommendedAdvanceFee('Mirpur, Dhaka');
        $this->assertEquals(100, $feeDhaka);

        $feeOutside = $this->fraudService->getRecommendedAdvanceFee('Agrabad, Chittagong');
        $this->assertEquals(150, $feeOutside);
    }

    public function test_blacklisted_phone_triggers_high_risk_score(): void
    {
        FraudBlacklist::create([
            'type' => 'phone',
            'value' => '01799998888',
            'reason' => 'Habitual COD rejector',
            'list_type' => 'blacklist',
            'created_by' => $this->admin->id,
        ]);

        $customer = Customer::create([
            'first_name' => 'Bad',
            'last_name' => 'Actor',
            'email' => 'fake@example.com',
            'phone' => '01799998888',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-999',
            'customer_id' => $customer->id,
            'subtotal' => 3000,
            'tax' => 0,
            'shipping_cost' => 100,
            'total' => 3100,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'shipping_address' => [
                'name' => 'Bad Actor',
                'phone' => '01799998888',
                'address' => 'Mirpur 10',
                'city' => 'Dhaka',
            ],
        ]);

        $assessment = $this->fraudService->evaluateOrder($order);

        $this->assertEquals(100, $assessment['score']);
        $this->assertEquals('high', $assessment['risk_level']);
        $this->assertEquals('blocked', $assessment['status']);
    }

    public function test_super_admin_can_access_fraud_hub(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.fraud.index'));
        $response->assertOk();
    }

    public function test_admin_can_lookup_phone_number_via_api(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.fraud.lookup'), [
            'phone' => '01712345678',
        ]);

        $response->assertOk();
        $response->assertJson([
            'phone' => '01712345678',
            'normalized' => '01712345678',
            'operator' => [
                'name' => 'Grameenphone',
            ],
        ]);
    }

    public function test_admin_can_request_and_confirm_advance_delivery_fee(): void
    {
        $customer = Customer::create([
            'first_name' => 'Naim',
            'last_name' => 'Islam',
            'email' => 'naim@example.com',
            'phone' => '01711223344',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-ADV-001',
            'customer_id' => $customer->id,
            'subtotal' => 6000,
            'tax' => 0,
            'shipping_cost' => 150,
            'total' => 6150,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'shipping_address' => [
                'name' => 'Naim Islam',
                'phone' => '01711223344',
                'address' => 'Agrabad',
                'city' => 'Chittagong',
            ],
        ]);

        // Request Advance
        $response = $this->actingAs($this->admin)->post(route('admin.fraud.request-advance', $order->id), [
            'amount' => 150,
        ]);
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals(150, (int) $order->advance_delivery_charge);
        $this->assertEquals('pending', $order->advance_payment_status);

        // Confirm Advance with bKash TrxID
        $confirmResponse = $this->actingAs($this->admin)->post(route('admin.fraud.confirm-advance', $order->id), [
            'advance_payment_method' => 'bKash',
            'advance_transaction_id' => 'BK99AA88ZZ',
        ]);
        $confirmResponse->assertRedirect();

        $order->refresh();
        $this->assertEquals('paid', $order->advance_payment_status);
        $this->assertEquals('BK99AA88ZZ', $order->advance_transaction_id);
    }

    public function test_admin_can_block_order_and_blacklist_customer(): void
    {
        $customer = Customer::create([
            'first_name' => 'Scammer',
            'last_name' => 'Fake',
            'email' => 'scam@test.com',
            'phone' => '01899112233',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-BLOCK-001',
            'customer_id' => $customer->id,
            'subtotal' => 12000,
            'tax' => 0,
            'shipping_cost' => 150,
            'total' => 12150,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'shipping_address' => [
                'name' => 'Scammer Fake',
                'phone' => '01899112233',
                'address' => 'Unknown street',
                'city' => 'Sylhet',
            ],
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.fraud.block', $order->id), [
            'reason' => 'Fake order with dummy delivery address',
        ]);

        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('blocked', $order->fraud_status);

        $this->assertTrue(FraudBlacklist::isBlacklisted('phone', '01899112233'));
    }
}
