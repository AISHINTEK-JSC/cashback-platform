<?php

namespace Database\Factories;

use App\Models\Platform;
use App\Models\TrackingLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrackingLink>
 */
class TrackingLinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $url = 'https://shopee.vn/san-pham-i.'.fake()->randomNumber(6).'.'.fake()->randomNumber(8);

        return [
            'user_id' => User::factory(),
            'platform_id' => Platform::factory(),
            'sub_id' => Str::lower(Str::random(16)),
            'original_url' => $url,
            'affiliate_url' => $url.'?aff_sub=x',
            'product_name' => fake()->sentence(4),
            'price' => fake()->numberBetween(5, 100) * 10000,
        ];
    }
}
