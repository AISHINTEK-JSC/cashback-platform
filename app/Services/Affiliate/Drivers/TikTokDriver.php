<?php

namespace App\Services\Affiliate\Drivers;

use App\Models\Platform;
use App\Services\Affiliate\AffiliateDriver;
use App\Services\Affiliate\AffiliateException;
use App\Services\Affiliate\Data\GeneratedLink;
use Carbon\CarbonInterface;

/**
 * Khung tích hợp TikTok Shop Affiliate. Cần hoàn thiện khi có tài khoản đối tác và tài liệu API:
 * - generateLink(): gọi API tạo link, gắn $subId vào tham số tracking của sàn.
 * - fetchOrders(): gọi báo cáo đơn theo khoảng thời gian, map về PlatformOrderData.
 */
class TikTokDriver implements AffiliateDriver
{
    public function generateLink(Platform $platform, string $url, string $subId): GeneratedLink
    {
        throw AffiliateException::notConfigured($platform->name);
    }

    public function fetchOrders(Platform $platform, CarbonInterface $from, CarbonInterface $to): iterable
    {
        throw AffiliateException::notConfigured($platform->name);
    }
}
