<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBlogTest extends TestCase
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

        $this->regularUser = User::factory()->create([
            'role' => 'customer',
        ]);
    }

    public function test_admin_can_view_blog_index(): void
    {
        BlogPost::create([
            'title' => 'Summer Style Guide',
            'slug' => 'summer-style-guide',
            'content' => '<p>Tips for summer fashion.</p>',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.blogs.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Blogs/Index')
            ->has('posts.data', 1)
            ->has('metrics')
            ->where('metrics.total_posts', 1)
        );
    }

    public function test_unauthorized_user_cannot_access_admin_blogs(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.blogs.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_blog_article(): void
    {
        $payload = [
            'title' => 'Top 10 Footwear Trends of 2026',
            'slug' => 'top-10-footwear-trends',
            'excerpt' => 'Sneakers, boots, and loafers leading fashion.',
            'content' => '<p>Here are the top footwear trends.</p>',
            'category' => 'Trends',
            'author_name' => 'Shoe Guru',
            'read_time_minutes' => 5,
            'is_published' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.blogs.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_posts', [
            'title' => 'Top 10 Footwear Trends of 2026',
            'slug' => 'top-10-footwear-trends',
            'author_name' => 'Shoe Guru',
            'is_published' => true,
        ]);
    }

    public function test_admin_can_update_blog_article(): void
    {
        $post = BlogPost::create([
            'title' => 'Original Title',
            'slug' => 'original-title',
            'content' => '<p>Original content</p>',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.blogs.update', $post), [
            'title' => 'Revised Title',
            'slug' => 'revised-title',
            'content' => '<p>Revised content</p>',
            'is_published' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'title' => 'Revised Title',
            'is_published' => false,
        ]);
    }

    public function test_admin_can_toggle_blog_status(): void
    {
        $post = BlogPost::create([
            'title' => 'Toggle Me',
            'slug' => 'toggle-me',
            'content' => '<p>Body</p>',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.blogs.toggle-status', $post));

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'is_published' => false,
        ]);
    }

    public function test_admin_can_delete_blog_article(): void
    {
        $post = BlogPost::create([
            'title' => 'Delete Me',
            'slug' => 'delete-me',
            'content' => '<p>Body</p>',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.blogs.destroy', $post));

        $response->assertRedirect();
        $this->assertDatabaseMissing('blog_posts', [
            'id' => $post->id,
        ]);
    }
}
