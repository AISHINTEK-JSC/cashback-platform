<?php

namespace App\Services\Affiliate;

use App\Models\Platform;
use App\Services\Affiliate\Drivers\AccessTradeDriver;
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
        'accesstrade' => AccessTradeDriver::class,
    ];

    /**
     * Nhãn driver hiển thị ở trang quản trị Sàn liên kết.
     *
     * @var array<string, string>
     */
    public const DRIVER_LABELS = [
        'fake' => 'Giả lập (dev/demo)',
        'accesstrade' => 'AccessTrade',
        'shopee' => 'Shopee Open API',
        'tiktok' => 'TikTok Shop API',
        'lazada' => 'Lazada API',
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
        'adidas' => ['adidas.com.vn'],
        'apple' => ['apple.com'],
    ];

    public function __construct(private readonly Container $container) {}

    public function driver(Platform $platform): AffiliateDriver
    {
        return $this->container->make(self::DRIVERS[$this->driverName($platform)] ?? FakeDriver::class);
    }

    /**
     * Driver chọn ở trang quản trị, chưa chọn thì theo biến AFFILIATE_*_DRIVER.
     */
    public function driverName(Platform $platform): string
    {
        return $platform->driver ?: config("cashback.drivers.{$platform->code}", 'fake');
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
