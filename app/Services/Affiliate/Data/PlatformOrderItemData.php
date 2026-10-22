<?php

namespace App\Services\Affiliate\Data;

final readonly class PlatformOrderItemData
{
    public function __construct(
        public string $itemId,
        public ?string $itemName,
        public ?string $categoryCode,
        public int $quantity,
        public int $price,
        public ?float $commissionRate,
        public int $commissionAmount,
        public ?string $status = null,
    ) {}
}
