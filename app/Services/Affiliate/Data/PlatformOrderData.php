<?php

namespace App\Services\Affiliate\Data;

use App\Enums\OrderStatus;
use Carbon\CarbonInterface;

/**
 * Đơn hàng đã chuẩn hoá từ báo cáo conversion của sàn.
 */
final readonly class PlatformOrderData
{
    /**
     * @param  list<PlatformOrderItemData>  $items
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $platformOrderId,
        public ?string $subId,
        public int $orderAmount,
        public int $commissionAmount,
        public OrderStatus $status,
        public string $platformStatus,
        public CarbonInterface $purchasedAt,
        public array $items = [],
        public array $raw = [],
    ) {}
}
