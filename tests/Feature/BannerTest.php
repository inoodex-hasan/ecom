<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerTest extends TestCase
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

    public function test_admin_can_view_banners_index(): void
    {
        Banner::create([
            'title' => 'Summer Mega Clearance',
            'subtitle' => 'Up to 70% Off All Categories',
            'placement' => 'hero_slider',
            'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.banners.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Banners/Index')
            ->has('banners', 1)
            ->has('metrics')
            ->where('metrics.total_banners', 1)
            ->where('metrics.active_count', 1)
            ->where('metrics.hero_slides', 1)
        );
    }

    public function test_unauthorized_user_cannot_access_banners(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.banners.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_banner(): void
    {
        $payload = [
            'title' => 'Black Friday Weekend Sale',
            'subtitle' => 'Exclusive online deals',
            'badge_text' => 'HOT DEAL',
            'image_url' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da',
            'button_text' => 'Shop Now',
            'link_url' => '/categories/clearance',
            'placement' => 'hero_slider',
            'sort_order' => 2,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->from(route('admin.banners.index'))
            ->post(route('admin.banners.store'), $payload);

        $response->assertRedirect(route('admin.banners.index'));
        $this->assertDatabaseHas('banners', [
            'title' => 'Black Friday Weekend Sale',
            'placement' => 'hero_slider',
            'badge_text' => 'HOT DEAL',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_banner(): void
    {
        $banner = Banner::create([
            'title' => 'Initial Title',
            'placement' => 'hero_slider',
            'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.banners.index'))
            ->put(route('admin.banners.update', $banner), [
                'title' => 'Updated Banner Title',
                'subtitle' => 'Updated Subtitle',
                'placement' => 'home_banner',
                'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8',
                'is_active' => false,
                'sort_order' => 5,
            ]);

        $response->assertRedirect(route('admin.banners.index'));
        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'title' => 'Updated Banner Title',
            'placement' => 'home_banner',
            'is_active' => false,
            'sort_order' => 5,
        ]);
    }

    public function test_admin_can_toggle_banner_status(): void
    {
        $banner = Banner::create([
            'title' => 'Toggle Me',
            'placement' => 'category_banner',
            'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.banners.index'))
            ->patch(route('admin.banners.toggle-status', $banner));

        $response->assertRedirect(route('admin.banners.index'));
        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_banner(): void
    {
        $banner = Banner::create([
            'title' => 'Delete Me',
            'placement' => 'popup_promo',
            'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.banners.index'))
            ->delete(route('admin.banners.destroy', $banner));

        $response->assertRedirect(route('admin.banners.index'));
        $this->assertDatabaseMissing('banners', [
            'id' => $banner->id,
        ]);
    }
}
