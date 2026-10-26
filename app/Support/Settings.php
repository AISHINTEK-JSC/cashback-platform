<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Đọc/ghi cấu hình vận hành trong bảng settings, có cache và fallback về config/cashback.php.
 */
class Settings
{
    private const CACHE_KEY = 'settings.all';

    /**
     * Khoá tích hợp (lưu mã hoá) => khoá config đọc từ .env khi admin chưa nhập.
     *
     * @var array<string, string>
     */
    public const SECRETS = [
        'accesstrade_api_key' => 'services.accesstrade.key',
        'google_client_id' => 'services.google.client_id',
        'google_client_secret' => 'services.google.client_secret',
        'facebook_client_id' => 'services.facebook.client_id',
        'facebook_client_secret' => 'services.facebook.client_secret',
    ];

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
     * Giá trị tích hợp admin đã nhập, chưa nhập thì lấy từ .env.
     */
    public function secret(string $key): ?string
    {
        $stored = $this->all()[$key] ?? null;

        if ($stored !== null) {
            try {
                return Crypt::decryptString($stored);
            } catch (DecryptException) {
                // APP_KEY đổi thì bản lưu cũ không giải mã được, dùng tạm giá trị .env
            }
        }

        return config(self::SECRETS[$key]) ?: null;
    }

    public function hasStoredSecret(string $key): bool
    {
        return ($this->all()[$key] ?? null) !== null;
    }

    public function setSecret(string $key, string $value): void
    {
        $this->set($key, Crypt::encryptString($value));
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
