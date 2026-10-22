<?php

namespace Database\Factories;

use App\Models\CommissionRule;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionRule>
 */
class CommissionRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'platform_id' => Platform::factory(),
            'category_code' => null,
            'user_rate' => 70,
            'f1_rate' => 10,
            'f2_rate' => 5,
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ];
    }
}
