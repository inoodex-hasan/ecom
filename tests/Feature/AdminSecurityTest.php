<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/products');
        $response->assertRedirect('/login');

        $response = $this->get('/admin/settings');
        $response->assertRedirect('/login');
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $user = User::factory()->create();

        // No permissions granted
        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertForbidden();

        $response = $this->actingAs($user)->get('/admin/products');
        $response->assertForbidden();

        $response = $this->actingAs($user)->get('/admin/settings');
        $response->assertForbidden();

        $response = $this->actingAs($user)->get('/admin/staff');
        $response->assertForbidden();

        $response = $this->actingAs($user)->post('/admin/coupons/validate', [
            'code' => 'SAVE10',
            'subtotal' => 100,
        ]);
        $response->assertForbidden();
    }

    public function test_user_with_permission_can_access_authorized_route(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.view');
        $user->givePermissionTo('products.view');

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertOk();

        $response = $this->actingAs($user)->get('/admin/products');
        $response->assertOk();

        // But still cannot access settings
        $settingsResponse = $this->actingAs($user)->get('/admin/settings');
        $settingsResponse->assertForbidden();
    }

    public function test_regular_user_dashboard_redirects_to_user_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();
    }

    public function test_staff_user_dashboard_redirects_to_admin_dashboard(): void
    {
        $staff = User::factory()->create();
        $staff->givePermissionTo('dashboard.view');

        $response = $this->actingAs($staff)->get('/dashboard');
        $response->assertRedirect(route('admin.dashboard'));
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

    public function test_non_super_admin_cannot_bulk_update_role_permissions(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Store Manager');
        $manager->givePermissionTo('staff.manage');

        $storeManagerRole = Role::findByName('Store Manager');

        $response = $this->actingAs($manager)->post('/admin/roles/bulk-permissions', [
            'matrix' => [
                [
                    'role_id' => $storeManagerRole->id,
                    'permissions' => ['staff.manage', 'settings.edit'],
                ],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_non_super_admin_cannot_update_single_role_permissions(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Store Manager');
        $manager->givePermissionTo('staff.manage');

        $storeManagerRole = Role::findByName('Store Manager');

        $response = $this->actingAs($manager)->post("/admin/roles/{$storeManagerRole->id}/permissions", [
            'permissions' => ['staff.manage', 'settings.edit'],
        ]);

        $response->assertForbidden();
    }

    public function test_super_admin_can_bulk_update_role_permissions(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $catalogRole = Role::findByName('Catalog Specialist');

        $response = $this->actingAs($superAdmin)->post('/admin/roles/bulk-permissions', [
            'matrix' => [
                [
                    'role_id' => $catalogRole->id,
                    'permissions' => ['products.view', 'categories.view'],
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertTrue($catalogRole->fresh()->hasPermissionTo('categories.view'));
    }
}
