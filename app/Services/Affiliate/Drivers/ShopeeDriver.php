<?php

namespace App\Services\Affiliate\Drivers;

use App\Enums\OrderStatus;
use App\Models\Platform;
use App\Services\Affiliate\AffiliateDriver;
use App\Services\Affiliate\AffiliateException;
use App\Services\Affiliate\Data\GeneratedLink;
use App\Services\Affiliate\Data\PlatformOrderData;
use App\Services\Affiliate\Data\PlatformOrderItemData;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Shopee Affiliate Open API (GraphQL).
 *
 * Cần khai báo trong api_config của sàn: app_id, secret. Endpoint mặc định cho thị trường VN.
 *
 * @see https://affiliate.shopee.vn/open_api
 */
class ShopeeDriver implements AffiliateDriver
{
    private const ENDPOINT = 'https://open-api.affiliate.shopee.vn/graphql';

    public function generateLink(Platform $platform, string $url, string $subId): GeneratedLink
    {
        $mutation = sprintf(
            'mutation { generateShortLink(input: { originUrl: %s, subIds: [%s] }) { shortLink } }',
            json_encode($url, JSON_UNESCAPED_SLASHES),
            json_encode($subId),
        );

        $shortLink = data_get($this->query($platform, $mutation), 'generateShortLink.shortLink');

        if (! $shortLink) {
            throw new AffiliateException('Shopee không trả về link rút gọn.');
        }

        return new GeneratedLink(...['affiliateUrl' => $shortLink, ...$this->productInfo($platform, $url)]);
    }

    public function fetchOrders(Platform $platform, CarbonInterface $from, CarbonInterface $to): iterable
    {
        $scrollId = '';

        do {
            $query = sprintf(
                '{ conversionReport(purchaseTimeStart: %d, purchaseTimeEnd: %d, limit: 500, scrollId: %s) {
                    nodes { purchaseTime conversionId utmContent
                      orders { orderId orderStatus
                        items { itemId itemName itemPrice qty actualAmount itemTotalCommission itemCommissionRate globalCategoryLv1Name displayItemStatus } } }
                    pageInfo { hasNextPage scrollId } } }',
                $from->getTimestamp(),
                $to->getTimestamp(),
                json_encode($scrollId),
            );

            $report = data_get($this->query($platform, $query), 'conversionReport', []);

            foreach (data_get($report, 'nodes', []) as $node) {
                foreach (data_get($node, 'orders', []) as $order) {
                    yield $this->mapOrder($node, $order);
                }
            }

            $scrollId = (string) data_get($report, 'pageInfo.scrollId', '');
        } while (data_get($report, 'pageInfo.hasNextPage') && $scrollId !== '');
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $order
     */
    private function mapOrder(array $node, array $order): PlatformOrderData
    {
        $items = array_map(fn (array $item) => new PlatformOrderItemData(
            itemId: (string) $item['itemId'],
            itemName: $item['itemName'] ?? null,
            categoryCode: $item['globalCategoryLv1Name'] ?? null,
            quantity: (int) ($item['qty'] ?? 1),
            price: (int) round((float) ($item['itemPrice'] ?? 0)),
            commissionRate: isset($item['itemCommissionRate']) ? (float) $item['itemCommissionRate'] * 100 : null,
            commissionAmount: (int) round((float) ($item['itemTotalCommission'] ?? 0)),
            status: $item['displayItemStatus'] ?? null,
        ), data_get($order, 'items', []));

        $platformStatus = (string) ($order['orderStatus'] ?? 'PENDING');

        return new PlatformOrderData(
            platformOrderId: (string) $order['orderId'],
            subId: $this->firstSubId($node['utmContent'] ?? null),
            orderAmount: (int) round(collect(data_get($order, 'items', []))->sum(fn ($i) => (float) ($i['actualAmount'] ?? 0))),
            commissionAmount: array_sum(array_map(fn (PlatformOrderItemData $i) => $i->commissionAmount, $items)),
            status: match (strtoupper($platformStatus)) {
                'COMPLETED' => OrderStatus::Confirmed,
                'CANCELLED', 'INVALID' => OrderStatus::Cancelled,
                default => OrderStatus::Pending,
            },
            platformStatus: $platformStatus,
            purchasedAt: Carbon::createFromTimestamp((int) $node['purchaseTime'], config('app.timezone')),
            items: $items,
            raw: ['conversion' => $node['conversionId'] ?? null, 'order' => $order],
        );
    }

    /**
     * utmContent dạng "sub1-sub2-sub3-sub4-sub5"; sub_id của hệ thống luôn nằm ở vị trí đầu.
     */
    private function firstSubId(?string $utmContent): ?string
    {
        $first = explode('-', (string) $utmContent)[0] ?? '';

        return $first !== '' ? $first : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function productInfo(Platform $platform, string $url): array
    {
        if (! preg_match('/i\.(\d+)\.(\d+)|product\/(\d+)\/(\d+)/', $url, $m)) {
            return [];
        }

        $shopId = $m[1] ?: $m[3];
        $itemId = $m[2] ?: $m[4];

        try {
            $node = data_get($this->query($platform, sprintf(
                '{ productOfferV2(shopId: %d, itemId: %d) { nodes { productName imageUrl priceMin commissionRate } } }',
                $shopId,
                $itemId,
            )), 'productOfferV2.nodes.0');
        } catch (Throwable) {
            $node = null;
        }

        return array_filter([
            'shopId' => $shopId,
            'productId' => $itemId,
            'productName' => $node['productName'] ?? null,
            'productImage' => $node['imageUrl'] ?? null,
            'price' => isset($node['priceMin']) ? (int) round((float) $node['priceMin']) : null,
            'commissionRate' => isset($node['commissionRate']) ? (float) $node['commissionRate'] * 100 : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function query(Platform $platform, string $query): array
    {
        $appId = $platform->config('app_id');
        $secret = $platform->config('secret');

        if (! $appId || ! $secret) {
            throw AffiliateException::notConfigured($platform->name);
        }

        $payload = json_encode(['query' => $query], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = time();
        $signature = hash('sha256', $appId.$timestamp.$payload.$secret);

        $response = Http::timeout(20)
            ->retry(2, 500, throw: false)
            ->withHeaders(['Authorization' => "SHA256 Credential={$appId}, Timestamp={$timestamp}, Signature={$signature}"])
            ->withBody($payload, 'application/json')
            ->post($platform->config('endpoint', self::ENDPOINT));

        if ($response->failed() || $response->json('errors')) {
            throw new AffiliateException('Shopee API lỗi: '.($response->json('errors.0.message') ?? $response->status()));
        }

        return (array) $response->json('data', []);
    }
}
