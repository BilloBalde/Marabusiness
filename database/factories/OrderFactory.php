<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'order_number' => Order::generateOrderNumber(),
            'status' => 'new',
            'grand_total' => fake()->randomFloat(2, 10, 500),
            'grand_total_usd' => fake()->randomFloat(2, 10, 500),
            'rate_to_usd' => 1,
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
            'shipping_amount' => 0,
            'total_paid' => 0,
            'total_remaining' => 0,
        ];
    }
}
