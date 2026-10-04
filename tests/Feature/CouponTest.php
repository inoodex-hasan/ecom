<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\User;
use App\Services\CouponService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
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

    public function test_admin_can_view_coupons_index(): void
    {
        Coupon::create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Coupons/Index')
            ->has('coupons', 1)
            ->has('metrics')
            ->where('metrics.total_coupons', 1)
            ->where('metrics.active_coupons', 1)
        );
    }

    public function test_unauthorized_user_cannot_access_coupons(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.coupons.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_percentage_coupon(): void
    {
        $payload = [
            'code' => 'summer20',
            'description' => '20% off summer sale',
            'type' => 'percentage',
            'value' => 20,
            'min_spend' => 50,
            'max_discount' => 30,
            'usage_limit_total' => 100,
            'usage_limit_per_customer' => 2,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->from(route('admin.coupons.index'))
            ->post(route('admin.coupons.store'), $payload);

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', [
            'code' => 'SUMMER20',
            'type' => 'percentage',
            'value' => 20,
            'min_spend' => 50,
            'max_discount' => 30,
            'usage_limit_total' => 100,
            'usage_limit_per_customer' => 2,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_free_shipping_coupon(): void
    {
        $payload = [
            'code' => 'SHIPFREE',
            'description' => 'Free shipping voucher',
            'type' => 'free_shipping',
            'min_spend' => 75,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->from(route('admin.coupons.index'))
            ->post(route('admin.coupons.store'), $payload);

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', [
            'code' => 'SHIPFREE',
            'type' => 'free_shipping',
            'value' => 0,
            'min_spend' => 75,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'OLDCODE',
            'type' => 'fixed_cart',
            'value' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.coupons.index'))
            ->put(route('admin.coupons.update', $coupon), [
                'code' => 'NEWCODE',
                'description' => 'Updated terms',
                'type' => 'fixed_cart',
                'value' => 15,
                'min_spend' => 40,
                'is_active' => false,
            ]);

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'NEWCODE',
            'value' => 15,
            'min_spend' => 40,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_toggle_coupon_status(): void
    {
        $coupon = Coupon::create([
            'code' => 'TOGGLEME',
            'type' => 'percentage',
            'value' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.coupons.index'))
            ->patch(route('admin.coupons.toggle-status', $coupon));

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'DELETEME',
            'type' => 'percentage',
            'value' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.coupons.index'))
            ->delete(route('admin.coupons.destroy', $coupon));

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseMissing('coupons', [
            'id' => $coupon->id,
        ]);
    }

    public function test_coupon_service_validates_and_calculates_discount(): void
    {
        $service = new CouponService;

        $coupon = Coupon::create([
            'code' => 'VIP25',
            'type' => 'percentage',
            'value' => 25,
            'min_spend' => 100,
            'max_discount' => 30,
            'is_active' => true,
        ]);

        // Cart under minimum spend
        $resultUnderMin = $service->validateAndApply('VIP25', 80.00);
        $this->assertFalse($resultUnderMin['valid']);
        $this->assertStringContainsString('Minimum order spend', $resultUnderMin['message']);

        // Cart with valid subtotal: 25% of $200 = $50, capped at max_discount $30
        $resultValid = $service->validateAndApply('VIP25', 200.00);
        $this->assertTrue($resultValid['valid']);
        $this->assertEquals(30.00, $resultValid['discount']);
    }

    public function test_coupon_service_enforces_usage_limits(): void
    {
        $service = new CouponService;

        $customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.coupon@example.com',
        ]);

        $coupon = Coupon::create([
            'code' => 'LIMITED1',
            'type' => 'fixed_cart',
            'value' => 10,
            'usage_limit_total' => 2,
            'usage_limit_per_customer' => 1,
            'is_active' => true,
        ]);

        // First usage should be valid
        $res1 = $service->validateAndApply('LIMITED1', 50.00, $customer->id);
        $this->assertTrue($res1['valid']);

        // Record first usage
        $service->recordUsage($coupon, 10.00, $customer->id);
        $coupon->refresh();

        $this->assertEquals(1, $coupon->total_used);

        // Second attempt by the same customer should fail per-customer limit
        $res2 = $service->validateAndApply('LIMITED1', 50.00, $customer->id);
        $this->assertFalse($res2['valid']);
        $this->assertStringContainsString('already reached the usage limit', $res2['message']);
    }

    public function test_coupon_validate_api_endpoint(): void
    {
        Coupon::create([
            'code' => 'API10',
            'type' => 'fixed_cart',
            'value' => 10,
            'min_spend' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.coupons.validate'), [
            'code' => 'API10',
            'subtotal' => 50,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'discount' => 10,
        ]);
    }
}
