<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerTest extends TestCase
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

    public function test_authorized_user_can_view_customers_index(): void
    {
        Customer::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.customers.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Customers/Index')
            ->has('customers.data', 3)
            ->has('filters')
        );
    }

    public function test_unauthorized_user_cannot_view_customers(): void
    {
        $customerUser = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customerUser)->get(route('admin.customers.index'));

        $response->assertStatus(403);
    }

    public function test_customers_index_filters_by_search_and_status(): void
    {
        Customer::factory()->create([
            'first_name' => 'SpecificSearchName',
            'status' => 'active',
        ]);
        Customer::factory()->create([
            'first_name' => 'OtherCustomer',
            'status' => 'blocked',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.customers.index', [
            'search' => 'SpecificSearchName',
            'status' => 'active',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Customers/Index')
            ->where('customers.total', 1)
            ->where('customers.data.0.first_name', 'SpecificSearchName')
        );
    }

    public function test_authorized_user_can_view_customer_details(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.customers.show', $customer));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Customers/Show')
            ->where('customer.id', $customer->id)
        );
    }

    public function test_authorized_user_can_update_customer_status(): void
    {
        $customer = Customer::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)->patch(route('admin.customers.update-status', $customer), [
            'status' => 'blocked',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('blocked', $customer->fresh()->status);
    }

    public function test_authorized_user_can_export_customers(): void
    {
        Customer::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.customers.export', ['format' => 'csv']));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition') ?? '', 'customers-export'));
    }
}
