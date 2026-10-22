<?php

namespace App\Services\Affiliate\Data;

/**
 * Kết quả tạo link affiliate, kèm thông tin sản phẩm nếu API sàn trả về.
 */
final readonly class GeneratedLink
{
    public function __construct(
        public string $affiliateUrl,
        public ?string $shopId = null,
        public ?string $productId = null,
        public ?string $productName = null,
        public ?string $productImage = null,
        public ?int $price = null,
        public ?float $commissionRate = null,
    ) {}
}
