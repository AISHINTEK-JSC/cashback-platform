<?php

use App\Models\CommissionRule;
use App\Models\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Sàn Shopee đang bật với quy tắc mặc định: user 70%, F1 10%, F2 5%.
 */
function shopeeWithDefaultRule(array $rule = []): Platform
{
    $platform = Platform::factory()->create();
    CommissionRule::factory()->for($platform)->create($rule);

    return $platform;
}
