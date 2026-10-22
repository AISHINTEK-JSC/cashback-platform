<?php

namespace App\Services\Affiliate\Drivers;

use App\Enums\OrderStatus;
use App\Models\Platform;
use App\Models\TrackingLink;
use App\Services\Affiliate\AffiliateDriver;
use App\Services\Affiliate\Data\GeneratedLink;
use App\Services\Affiliate\Data\PlatformOrderData;
use App\Services\Affiliate\Data\PlatformOrderItemData;
use Carbon\CarbonInterface;

/**
 * Driver giả lập cho dev/demo. Sinh đơn tất định từ các tracking link đã được click:
 * cùng một link luôn cho cùng một đơn, nên chạy sync nhiều lần vẫn idempotent.
 */
class FakeDriver implements AffiliateDriver
{
    public function generateLink(Platform $platform, string $url, string $subId): GeneratedLink
    {
        $seed = crc32($url);

        return new GeneratedLink(
            affiliateUrl: $url.(str_contains($url, '?') ? '&' : '?').http_build_query(['aff_sub' => $subId]),
            productId: (string) ($seed % 100000000),
            productName: 'Sản phẩm demo #'.($seed % 1000),
            price: (($seed % 50) + 1) * 10000,
            commissionRate: [4.0, 6.0, 8.0, 10.0][$seed % 4],
        );
    }

    public function fetchOrders(Platform $platform, CarbonInterface $from, CarbonInterface $to): iterable
    {
        $links = TrackingLink::query()
            ->where('platform_id', $platform->id)
            ->where('click_count', '>', 0)
            ->whereBetween('created_at', [$from->copy()->subDays(60), $to])
            ->get();

        foreach ($links as $link) {
            $seed = crc32($link->sub_id);
            $price = $link->price ?: (($seed % 50) + 1) * 10000;
            $quantity = ($seed % 3) + 1;
            $amount = $price * $quantity;
            $rate = (float) ($link->estimated_rate ? 8 : 6);
            $commission = (int) floor($amount * $rate / 100);
            $ageHours = $link->created_at->diffInHours(now());

            $status = match (true) {
                $seed % 10 === 0 => OrderStatus::Cancelled,
                $ageHours >= 1 => OrderStatus::Confirmed,
                default => OrderStatus::Pending,
            };

            yield new PlatformOrderData(
                platformOrderId: 'FAKE'.strtoupper(substr(md5($link->sub_id), 0, 12)),
                subId: $link->sub_id,
                orderAmount: $amount,
                commissionAmount: $commission,
                status: $status,
                platformStatus: strtoupper($status->value),
                purchasedAt: $link->created_at->copy()->addMinutes(5),
                items: [
                    new PlatformOrderItemData(
                        itemId: $link->product_id ?? (string) $seed,
                        itemName: $link->product_name ?? 'Sản phẩm demo',
                        categoryCode: null,
                        quantity: $quantity,
                        price: $price,
                        commissionRate: $rate,
                        commissionAmount: $commission,
                    ),
                ],
                raw: ['driver' => 'fake', 'sub_id' => $link->sub_id],
            );
        }
    }
}
