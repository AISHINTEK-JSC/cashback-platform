<?php

namespace App\Services\Affiliate;

use App\Models\Platform;
use App\Services\Affiliate\Data\GeneratedLink;
use App\Services\Affiliate\Data\PlatformOrderData;
use Carbon\CarbonInterface;

/**
 * Hợp đồng chung cho mọi sàn. Thêm sàn mới = viết 1 class implement interface này
 * và khai báo trong config/cashback.php.
 */
interface AffiliateDriver
{
    /**
     * Tạo link affiliate gắn sub_id để khớp đơn về user.
     */
    public function generateLink(Platform $platform, string $url, string $subId): GeneratedLink;

    /**
     * Lấy các đơn phát sinh/cập nhật trong khoảng thời gian.
     *
     * @return iterable<PlatformOrderData>
     */
    public function fetchOrders(Platform $platform, CarbonInterface $from, CarbonInterface $to): iterable;
}
