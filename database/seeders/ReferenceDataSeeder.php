<?php

namespace Database\Seeders;

use App\Models\CommissionRule;
use App\Models\Platform;
use App\Models\UserLevel;
use Illuminate\Database\Seeder;

/**
 * Dữ liệu nền cần cho mọi môi trường: hạng thành viên, sàn, tỷ lệ chia mặc định.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Đồng', 'min_approved_orders' => 0, 'bonus_rate' => 0],
            ['name' => 'Bạc', 'min_approved_orders' => 10, 'bonus_rate' => 3],
            ['name' => 'Vàng', 'min_approved_orders' => 50, 'bonus_rate' => 5],
            ['name' => 'Kim cương', 'min_approved_orders' => 200, 'bonus_rate' => 10],
        ] as $level) {
            UserLevel::query()->updateOrCreate(['name' => $level['name']], $level);
        }

        foreach ([
            ['code' => 'shopee', 'name' => 'Shopee', 'lock_days' => 35, 'is_active' => true],
            ['code' => 'tiktok', 'name' => 'TikTok Shop', 'lock_days' => 45, 'is_active' => true],
            ['code' => 'lazada', 'name' => 'Lazada', 'lock_days' => 45, 'is_active' => true],
            ['code' => 'adidas', 'name' => 'Adidas', 'lock_days' => 60, 'is_active' => true],
            ['code' => 'apple', 'name' => 'Apple', 'lock_days' => 90, 'is_active' => false, 'is_coming_soon' => true],
        ] as $data) {
            $platform = Platform::query()->firstOrCreate(['code' => $data['code']], $data);

            CommissionRule::query()->firstOrCreate(
                ['platform_id' => $platform->id, 'category_code' => null],
                ['user_rate' => 70, 'f1_rate' => 10, 'f2_rate' => 5, 'effective_from' => now()->startOfYear()],
            );
        }
    }
}
