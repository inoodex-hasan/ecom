<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected Product $product;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->regularUser = User::factory()->create();

        $this->customer = Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@example.com',
        ]);

        $this->product = Product::create([
            'name' => 'Ergonomic Desk Chair',
            'sku' => 'CHAIR-ERGO-01',
            'price' => 249.99,
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
            'status' => 'published',
        ]);
    }

    public function test_admin_can_view_reviews_index(): void
    {
        Review::create([
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
            'rating' => 5,
            'title' => 'Top Quality',
            'comment' => 'Very comfortable and easy to assemble.',
            'reviewer_name' => 'Alice Smith',
            'reviewer_email' => 'alice@example.com',
            'status' => 'pending',
            'is_verified_purchase' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reviews.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Reviews/Index')
            ->has('reviews.data', 1)
            ->has('metrics')
            ->where('metrics.total_reviews', 1)
            ->where('metrics.pending_count', 1)
        );
    }

    public function test_unauthorized_user_cannot_access_reviews(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.reviews.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_approve_review_and_updates_product_rating_metrics(): void
    {
        $review = Review::create([
            'product_id' => $this->product->id,
            'customer_id' => $this->customer->id,
            'rating' => 5,
            'comment' => 'Brilliant item!',
            'reviewer_name' => 'Alice Smith',
            'status' => 'pending',
        ]);

        $this->assertEquals(0, $this->product->fresh()->rating_count);
        $this->assertEquals(0.00, $this->product->fresh()->rating_avg);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.reviews.index'))
            ->patch(route('admin.reviews.update-status', $review), [
                'status' => 'approved',
            ]);

        $response->assertRedirect(route('admin.reviews.index'));
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => 'approved',
        ]);

        $updatedProduct = $this->product->fresh();
        $this->assertEquals(1, $updatedProduct->rating_count);
        $this->assertEquals(5.00, $updatedProduct->rating_avg);
    }

    public function test_admin_can_reject_review_and_excludes_from_product_rating(): void
    {
        $review = Review::create([
            'product_id' => $this->product->id,
            'rating' => 1,
            'comment' => 'Terrible experience.',
            'reviewer_name' => 'Anonymous',
            'status' => 'approved',
        ]);

        $this->product->updateRatingMetrics();
        $this->assertEquals(1, $this->product->fresh()->rating_count);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.reviews.index'))
            ->patch(route('admin.reviews.update-status', $review), [
                'status' => 'rejected',
            ]);

        $response->assertRedirect(route('admin.reviews.index'));
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => 'rejected',
        ]);

        $updatedProduct = $this->product->fresh();
        $this->assertEquals(0, $updatedProduct->rating_count);
        $this->assertEquals(0.00, $updatedProduct->rating_avg);
    }

    public function test_admin_can_post_merchant_reply(): void
    {
        $review = Review::create([
            'product_id' => $this->product->id,
            'rating' => 4,
            'comment' => 'Good product but shipping took 4 days.',
            'reviewer_name' => 'Alice Smith',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.reviews.index'))
            ->post(route('admin.reviews.reply', $review), [
                'merchant_reply' => 'Thank you Alice! We are upgrading our express courier routes this month.',
            ]);

        $response->assertRedirect(route('admin.reviews.index'));
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'merchant_reply' => 'Thank you Alice! We are upgrading our express courier routes this month.',
        ]);

        $this->assertNotNull($review->fresh()->merchant_replied_at);
    }

    public function test_admin_can_delete_review_and_recalculates_metrics(): void
    {
        $review = Review::create([
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Great chair!',
            'reviewer_name' => 'Alice Smith',
            'status' => 'approved',
        ]);

        $this->product->updateRatingMetrics();
        $this->assertEquals(1, $this->product->fresh()->rating_count);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.reviews.index'))
            ->delete(route('admin.reviews.destroy', $review));

        $response->assertRedirect(route('admin.reviews.index'));
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);

        $this->assertEquals(0, $this->product->fresh()->rating_count);
        $this->assertEquals(0.00, $this->product->fresh()->rating_avg);
    }

    public function test_multiple_reviews_calculate_accurate_average_rating(): void
    {
        Review::create([
            'product_id' => $this->product->id,
            'rating' => 5,
            'reviewer_name' => 'Buyer 1',
            'status' => 'approved',
        ]);

        Review::create([
            'product_id' => $this->product->id,
            'rating' => 3,
            'reviewer_name' => 'Buyer 2',
            'status' => 'approved',
        ]);

        $this->product->updateRatingMetrics();
        $fresh = $this->product->fresh();

        $this->assertEquals(2, $fresh->rating_count);
        $this->assertEquals(4.00, $fresh->rating_avg);
    }
}
