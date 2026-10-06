<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 5000);
        $shipping = 100.0;
        $total = $subtotal + $shipping;

        return [
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'customer_id' => Customer::factory(),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => $shipping,
            'total' => $total,
            'shipping_address' => [
                'name' => fake()->name(),
                'phone' => fake()->phoneNumber(),
                'address' => fake()->streetAddress(),
                'city' => fake()->city(),
            ],
            'billing_address' => [
                'name' => fake()->name(),
                'phone' => fake()->phoneNumber(),
                'address' => fake()->streetAddress(),
                'city' => fake()->city(),
            ],
            'notes' => fake()->sentence(),
            'fraud_risk_level' => 'low',
            'fraud_status' => 'clean',
        ];
    }
}
