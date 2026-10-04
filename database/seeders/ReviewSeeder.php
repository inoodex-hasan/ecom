<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::take(4)->get();
        if ($products->isEmpty()) {
            return;
        }

        $customer = Customer::first() ?? Customer::create([
            'first_name' => 'Sophia',
            'last_name' => 'Anderson',
            'email' => 'sophia.anderson@example.com',
        ]);

        $samples = [
            [
                'product_id' => $products[0]->id,
                'customer_id' => $customer->id,
                'rating' => 5,
                'title' => 'Absolute perfection! Highly recommend.',
                'comment' => 'I was hesitant at first because of the price, but the quality blew me away. The stitching and material are ultra-premium, exactly like the photos. Arrived in 2 days in beautiful packaging!',
                'photos' => [
                    'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600',
                    'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=600',
                ],
                'reviewer_name' => 'Sophia Anderson',
                'reviewer_email' => 'sophia.anderson@example.com',
                'status' => 'approved',
                'is_verified_purchase' => true,
                'merchant_reply' => "Thank you so much for your kind words, Sophia! We're thrilled that you loved the craftsmanship. Let us know if you ever need any assistance!",
                'merchant_replied_at' => now()->subDay(),
                'created_at' => now()->subDays(2),
            ],
            [
                'product_id' => $products[0]->id,
                'customer_id' => null,
                'rating' => 4,
                'title' => 'Great value for money, slightly delayed delivery',
                'comment' => 'The product itself is 5 stars, works flawlessly. Taking 1 star off only because the regional delivery took 4 days instead of 2. Overall very satisfied with the customer service response.',
                'photos' => null,
                'reviewer_name' => 'Marcus Vance',
                'reviewer_email' => 'marcus.v@example.com',
                'status' => 'approved',
                'is_verified_purchase' => true,
                'merchant_reply' => null,
                'merchant_replied_at' => null,
                'created_at' => now()->subDays(3),
            ],
            [
                'product_id' => $products[1]->id ?? $products[0]->id,
                'customer_id' => null,
                'rating' => 5,
                'title' => 'Exceeded my expectations on all fronts',
                'comment' => 'Feels sturdy and sleek. Fits our workspace decor seamlessly. Definitely buying a second one for my home office setup.',
                'photos' => [
                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600',
                ],
                'reviewer_name' => 'Emily Chen',
                'reviewer_email' => 'emily.chen@example.com',
                'status' => 'pending',
                'is_verified_purchase' => false,
                'merchant_reply' => null,
                'merchant_replied_at' => null,
                'created_at' => now()->subHours(4),
            ],
            [
                'product_id' => $products[2]->id ?? $products[0]->id,
                'customer_id' => null,
                'rating' => 2,
                'title' => 'Color does not match the monitor display',
                'comment' => 'The texture is good, but the shade was much darker in real life than pictured on the website. Would appreciate a return or replacement policy clarification.',
                'photos' => null,
                'reviewer_name' => 'David Miller',
                'reviewer_email' => 'david.m@example.com',
                'status' => 'pending',
                'is_verified_purchase' => true,
                'merchant_reply' => null,
                'merchant_replied_at' => null,
                'created_at' => now()->subHours(8),
            ],
            [
                'product_id' => $products[3]->id ?? $products[0]->id,
                'customer_id' => null,
                'rating' => 1,
                'title' => 'Spam advertisement links',
                'comment' => 'Check out our cheap discount site at http://example-fake-scam.com for free gift cards!',
                'photos' => null,
                'reviewer_name' => 'Bot9928',
                'reviewer_email' => 'bot@spamsite.xyz',
                'status' => 'spam',
                'is_verified_purchase' => false,
                'merchant_reply' => null,
                'merchant_replied_at' => null,
                'created_at' => now()->subDays(5),
            ],
        ];

        foreach ($samples as $sample) {
            Review::create($sample);
        }

        foreach ($products as $product) {
            $product->updateRatingMetrics();
        }
    }
}
