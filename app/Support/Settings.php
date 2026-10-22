<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Đọc/ghi cấu hình vận hành trong bảng settings, có cache và fallback về config/cashback.php.
 */
class Settings
{
    private const CACHE_KEY = 'settings.all';

    public function get(string $key, mixed $default = null): mixed
    {
        $configDefault = config("cashback.settings.{$key}", $default);
        $stored = $this->all()[$key] ?? null;

        if ($stored === null) {
            return $configDefault;
        }

        return match (true) {
            is_int($configDefault) => (int) $stored,
            is_float($configDefault) => (float) $stored,
            is_bool($configDefault) => filter_var($stored, FILTER_VALIDATE_BOOLEAN),
            default => $stored,
        };
    }

    public function set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => is_bool($value) ? (int) $value : $value]);

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<int, int>
     */
    public function checkinRewards(): array
    {
        return array_values(array_map('intval', array_filter(explode(',', (string) $this->get('checkin_rewards')), 'strlen')));
    }

    /**
     * @return array<string, string|null>
     */
    private function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), fn () => Setting::query()->pluck('value', 'key')->all());
    }
}
