<?php

use App\Support\Settings;

if (! function_exists('vnd')) {
    /**
     * Định dạng số tiền VND: 1234567 => "1.234.567đ".
     */
    function vnd(?int $amount): string
    {
        return number_format($amount ?? 0, 0, ',', '.').'đ';
    }
}

if (! function_exists('setting')) {
    /**
     * Đọc cấu hình vận hành (bảng settings, fallback config/cashback.php).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}
