<?php

namespace App\Services\Affiliate;

use App\Models\Platform;
use App\Services\Affiliate\Drivers\FakeDriver;
use App\Services\Affiliate\Drivers\LazadaDriver;
use App\Services\Affiliate\Drivers\ShopeeDriver;
use App\Services\Affiliate\Drivers\TikTokDriver;
use Illuminate\Contracts\Container\Container;

/**
 * Chọn driver theo sàn và nhận diện sàn từ URL người dùng dán vào.
 */
class AffiliateManager
{
    /**
     * @var array<string, class-string<AffiliateDriver>>
     */
    private const DRIVERS = [
        'fake' => FakeDriver::class,
        'shopee' => ShopeeDriver::class,
        'tiktok' => TikTokDriver::class,
        'lazada' => LazadaDriver::class,
    ];

    /**
     * Tên miền (kể cả link rút gọn) của từng sàn.
     *
     * @var array<string, list<string>>
     */
    private const HOSTS = [
        'shopee' => ['shopee.vn', 'shope.ee', 'shp.ee', 's.shopee.vn', 'vn.shp.ee'],
        'tiktok' => ['tiktok.com', 'vt.tiktok.com', 'vm.tiktok.com', 'shop.tiktok.com'],
        'lazada' => ['lazada.vn', 's.lazada.vn', 'c.lazada.vn'],
    ];

    public function __construct(private readonly Container $container) {}

    public function driver(Platform $platform): AffiliateDriver
    {
        $name = config("cashback.drivers.{$platform->code}", 'fake');

        return $this->container->make(self::DRIVERS[$name] ?? FakeDriver::class);
    }

    public function detectPlatformCode(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^(www|m)\./', '', $host);

        foreach (self::HOSTS as $code => $hosts) {
            foreach ($hosts as $candidate) {
                if ($host === $candidate || str_ends_with($host, '.'.$candidate)) {
                    return $code;
                }
            }
        }

        return null;
    }
}
