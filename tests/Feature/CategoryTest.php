<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    public function test_admin_can_view_categories_index(): void
    {
        Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.categories.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Categories/Index')
            ->has('categories', 1)
            ->has('metrics')
        );
    }

    public function test_admin_can_create_category_with_unique_slug(): void
    {
        // First category
        $response1 = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'name' => 'Fashion & Apparel',
            'is_active' => true,
        ]);
        $response1->assertRedirect();
        $this->assertDatabaseHas('categories', ['slug' => 'fashion-apparel']);

        // Second category with identical name should get auto-incremented unique slug
        $response2 = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'name' => 'Fashion & Apparel',
            'is_active' => true,
        ]);
        $response2->assertRedirect();
        $this->assertDatabaseHas('categories', ['slug' => 'fashion-apparel-1']);
    }

    public function test_admin_can_update_category_and_handles_slug_collisions(): void
    {
        $cat1 = Category::create([
            'name' => 'Gadgets',
            'slug' => 'gadgets',
            'is_active' => true,
        ]);

        $cat2 = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);

        // Updating cat2 with name 'Gadgets' and empty slug should not crash with unique constraint
        $response = $this->actingAs($this->admin)->put(route('admin.categories.update', $cat2), [
            'name' => 'Gadgets',
            'slug' => '',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertEquals('gadgets-1', $cat2->fresh()->slug);
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $category = Category::create([
            'name' => 'Hardware',
            'slug' => 'hardware',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
            'name' => 'Hardware',
            'parent_id' => $category->id,
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_admin_can_toggle_category_status(): void
    {
        $category = Category::create([
            'name' => 'Furniture',
            'slug' => 'furniture',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.categories.toggle-status', $category));

        $response->assertRedirect();
        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_deleting_category_detaches_products_and_reparents_children(): void
    {
        $parent = Category::create([
            'name' => 'Parent Dept',
            'slug' => 'parent-dept',
            'is_active' => true,
        ]);

        $child = Category::create([
            'name' => 'Sub Dept',
            'slug' => 'sub-dept',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $parent->id,
            'name' => 'Sample Prod',
            'sku' => 'SMPL-1',
            'price' => 10,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $parent));

        $response->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $parent->id]);
        $this->assertNull($product->fresh()->category_id);
        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_user_without_category_permission_is_forbidden(): void
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)->get(route('admin.categories.index'));
        $response->assertForbidden();

        $response = $this->actingAs($regularUser)->post(route('admin.categories.store'), [
            'name' => 'Hacked Category',
        ]);
        $response->assertForbidden();
    }
}
