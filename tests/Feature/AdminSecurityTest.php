<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guests_cannot_access_admin_dashboard_or_resources(): void
    {
        $response = $this->get('/admin/products');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/settings');
        $response->assertRedirect('/login');
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $user = User::factory()->create();

        // No permissions granted
        $response = $this->actingAs($user)->get('/admin/products');
        $response->assertForbidden();

        $response = $this->actingAs($user)->get('/admin/settings');
        $response->assertForbidden();

        $response = $this->actingAs($user)->get('/admin/staff');
        $response->assertForbidden();
    }

    public function test_user_with_permission_can_access_authorized_route(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('products.view');

        $response = $this->actingAs($user)->get('/admin/products');
        $response->assertOk();

        // But still cannot access settings
        $settingsResponse = $this->actingAs($user)->get('/admin/settings');
        $settingsResponse->assertForbidden();
    }

    public function test_super_admin_can_access_all_admin_routes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($admin)->get('/admin/products')->assertOk();
        $this->actingAs($admin)->get('/admin/orders')->assertOk();
        $this->actingAs($admin)->get('/admin/categories')->assertOk();
        $this->actingAs($admin)->get('/admin/customers')->assertOk();
        $this->actingAs($admin)->get('/admin/staff')->assertOk();
        $this->actingAs($admin)->get('/admin/settings')->assertOk();
    }

    public function test_non_super_admin_cannot_create_super_admin_account(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Store Manager');
        $manager->givePermissionTo('staff.manage');

        $response = $this->actingAs($manager)->post('/admin/staff', [
            'name' => 'Hacker Admin',
            'email' => 'hacker@example.com',
            'password' => 'Password123!',
            'role' => 'Super Admin',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    public function test_non_super_admin_cannot_delete_super_admin_account(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $manager = User::factory()->create();
        $manager->assignRole('Store Manager');
        $manager->givePermissionTo('staff.manage');

        $response = $this->actingAs($manager)->delete("/admin/staff/{$superAdmin->id}");
        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }
}
