<?php

namespace Database\Factories;

use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserLevel>
 */
class UserLevelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Đồng', 'Bạc', 'Vàng', 'Kim cương']),
            'min_approved_orders' => 0,
            'bonus_rate' => 0,
        ];
    }
}
