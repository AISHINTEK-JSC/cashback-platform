<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Platform;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'platform_id' => Platform::factory(),
            'code' => strtoupper(fake()->bothify('SALE####')),
            'title' => 'Giảm giá cho đơn từ 0đ',
            'discount_type' => DiscountType::Percent,
            'discount_value' => fake()->randomElement([5, 10, 15, 20]),
            'min_order' => 0,
            'max_discount' => 50000,
            'expires_at' => now()->addDays(7),
            'is_active' => true,
        ];
    }
}
