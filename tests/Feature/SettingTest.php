<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingTest extends TestCase
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

    public function test_admin_can_view_settings_index(): void
    {
        Setting::set('store_name', 'ApexStore');

        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/Index')
            ->has('settings')
        );
    }

    public function test_admin_can_update_settings(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'settings' => [
                'store_name' => 'Elite Commerce',
                'store_phone' => '01711223344',
                'currency_symbol' => '৳',
            ],
        ]);

        $response->assertRedirect();
        $this->assertEquals('Elite Commerce', Setting::get('store_name'));
        $this->assertEquals('01711223344', Setting::get('store_phone'));
        $this->assertEquals('৳', Setting::get('currency_symbol'));
    }

    public function test_updating_settings_clears_cached_site_settings(): void
    {
        Cache::put('site_settings', ['store_name' => 'Old Store'], 3600);

        $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'settings' => [
                'store_name' => 'Refreshed Store',
            ],
        ]);

        $this->assertFalse(Cache::has('site_settings'));
    }

    public function test_unauthorized_user_cannot_access_or_update_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.settings.index'));
        $response->assertForbidden();

        $response = $this->actingAs($user)->post(route('admin.settings.update'), [
            'settings' => [
                'store_name' => 'Hacked Store',
            ],
        ]);
        $response->assertForbidden();
    }
}
