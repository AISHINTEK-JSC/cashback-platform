<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'platform_id' => Platform::factory(),
            'platform_order_id' => (string) fake()->unique()->randomNumber(9),
            'order_amount' => 500000,
            'commission_amount' => 40000,
            'cashback_amount' => 0,
            'status' => OrderStatus::Pending,
            'purchased_at' => now(),
        ];
    }
}
