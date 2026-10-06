<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');
    }

    public function test_super_admin_can_view_staff_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.staff.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Staff/Index')
            ->has('staff')
            ->has('roles')
        );
    }

    public function test_super_admin_can_create_staff_member(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.staff.store'), [
            'name' => 'Fulfillment Agent',
            'email' => 'agent@ecom.test',
            'password' => 'SecurePass123!',
            'role' => 'Fulfillment Staff',
            'permissions' => ['orders.view', 'orders.edit'],
        ]);

        $response->assertRedirect(route('admin.staff.index'));
        $this->assertDatabaseHas('users', ['email' => 'agent@ecom.test']);

        $user = User::where('email', 'agent@ecom.test')->first();
        $this->assertTrue($user->hasRole('Fulfillment Staff'));
        $this->assertTrue($user->hasPermissionTo('orders.edit'));
    }

    public function test_super_admin_can_update_staff_member(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('Catalog Specialist');

        $response = $this->actingAs($this->superAdmin)->put(route('admin.staff.update', $staff), [
            'name' => 'Lead Specialist',
            'email' => $staff->email,
            'role' => 'Catalog Specialist',
            'permissions' => ['products.view', 'products.create'],
        ]);

        $response->assertRedirect(route('admin.staff.index'));
        $this->assertEquals('Lead Specialist', $staff->fresh()->name);
    }

    public function test_super_admin_can_delete_staff_member(): void
    {
        $staff = User::factory()->create(['name' => 'Temporary Worker']);
        $staff->assignRole('Customer Support');

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.staff.destroy', $staff));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_cannot_delete_last_remaining_super_admin(): void
    {
        // $this->superAdmin is the only Super Admin
        $response = $this->actingAs($this->superAdmin)->delete(route('admin.staff.destroy', $this->superAdmin));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    public function test_non_super_admin_cannot_assign_super_admin_role(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Store Manager');
        $manager->givePermissionTo('staff.manage');

        $staff = User::factory()->create();
        $staff->assignRole('Customer Support');

        $response = $this->actingAs($manager)->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => 'Super Admin',
        ]);

        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_access_staff_endpoints(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.staff.index'));
        $response->assertForbidden();

        $response = $this->actingAs($user)->post(route('admin.staff.store'), [
            'name' => 'Unauthorized Staff',
            'email' => 'unauth@test.com',
            'password' => 'Password123!',
            'role' => 'Store Manager',
        ]);
        $response->assertForbidden();
    }
}
