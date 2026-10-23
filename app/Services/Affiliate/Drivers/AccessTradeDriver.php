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
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * AccessTrade Publisher API: tạo deep link và lấy giao dịch cho nhiều sàn qua cùng một tài khoản.
 *
 * Key lấy từ ACCESSTRADE_API_KEY. Campaign của sàn lấy từ config/services.php,
 * ghi đè được bằng campaign_id, merchant trong api_config của sàn.
 *
 * @see https://developers.accesstrade.vn
 */
class AccessTradeDriver implements AffiliateDriver
{
    /**
     * API giao dịch chỉ cho phép khoảng thời gian dưới 31 ngày mỗi lần gọi.
     */
    private const MAX_WINDOW_DAYS = 30;

    private const PAGE_SIZE = 100;

    private const STATUS_PENDING = 0;

    private const STATUS_APPROVED = 1;

    private const STATUS_REJECTED = 2;

    public function generateLink(Platform $platform, string $url, string $subId): GeneratedLink
    {
        $response = $this->request()->post('product_link/create', [
            'campaign_id' => $this->campaignId($platform),
            'urls' => [$url],
            'url_enc' => true,
            'utm_source' => 'cashback',
            'utm_medium' => 'web',
            'utm_content' => $subId,
            'sub1' => $subId,
        ]);

        $link = $response->json('data.success_link.0');

        if (! $link) {
            $message = $response->json('data.error_link.0.message') ?? $response->json('message') ?? $response->status();

            throw new AffiliateException("AccessTrade không tạo được link {$platform->name}: {$message}");
        }

        return new GeneratedLink(
            affiliateUrl: $link['short_link'] ?: $link['aff_link'],
            productId: preg_match('/product\/(\d+)/', $url, $m) ? $m[1] : null,
        );
    }

    public function fetchOrders(Platform $platform, CarbonInterface $from, CarbonInterface $to): iterable
    {
        $merchant = $this->merchant($platform);
        $windowStart = $from->copy();

        while ($windowStart < $to) {
            $windowEnd = $windowStart->copy()->addDays(self::MAX_WINDOW_DAYS)->min($to);

            // Một đơn gồm nhiều dòng giao dịch (từng sản phẩm, thưởng thêm) có thể nằm ở nhiều trang
            $rows = collect($this->transactions($merchant, $windowStart, $windowEnd))->groupBy('transaction_id');

            foreach ($rows as $transactionId => $orderRows) {
                yield $this->mapOrder((string) $transactionId, $orderRows->all());
            }

            $windowStart = $windowEnd;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function transactions(string $merchant, CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = [];
        $page = 1;

        do {
            $response = $this->request()->get('transactions', [
                'merchant' => $merchant,
                'since' => $from->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                'until' => $to->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
                'page' => $page,
                'limit' => self::PAGE_SIZE,
            ]);

            $this->ensureSuccessful($response);

            $data = (array) $response->json('data', []);
            array_push($rows, ...$data);
            $page++;
        } while (count($data) === self::PAGE_SIZE && count($rows) < (int) $response->json('total', 0));

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function mapOrder(string $transactionId, array $rows): PlatformOrderData
    {
        $items = array_map(fn (array $row) => new PlatformOrderItemData(
            itemId: (string) $row['product_id'],
            itemName: ($row['product_name'] ?? '') !== '' ? $row['product_name'] : ($row['is_brand_bonus'] ? 'Thưởng thêm từ nhãn hàng' : null),
            categoryCode: ($row['category_name'] ?? '') !== '' ? $row['category_name'] : null,
            quantity: (int) ($row['product_quantity'] ?? 1),
            price: (int) round((float) ($row['product_price'] ?? 0)),
            commissionRate: (float) ($row['transaction_value'] ?? 0) > 0
                ? round((float) $row['commission'] / (float) $row['transaction_value'] * 100, 2)
                : null,
            commissionAmount: (int) round((float) ($row['commission'] ?? 0)),
            status: match ((int) $row['status']) {
                self::STATUS_APPROVED => 'approved',
                self::STATUS_REJECTED => 'rejected',
                default => 'pending',
            },
        ), $rows);

        $statuses = array_map(fn (array $row) => (int) $row['status'], $rows);
        $liveRows = array_filter($rows, fn (array $row) => (int) $row['status'] !== self::STATUS_REJECTED);
        $first = $rows[0];

        $status = match (true) {
            $liveRows === [] => OrderStatus::Cancelled,
            ! in_array(self::STATUS_PENDING, $statuses, true) => OrderStatus::Confirmed,
            default => OrderStatus::Pending,
        };

        return new PlatformOrderData(
            platformOrderId: $transactionId,
            subId: data_get($first, '_extra.sub_params.sub1') ?: (($first['utm_content'] ?? '') ?: null),
            orderAmount: (int) round(collect($rows)->reject(fn (array $row) => $row['is_brand_bonus'] ?? false)->sum('transaction_value')),
            commissionAmount: (int) round(collect($liveRows)->sum('commission')),
            status: $status,
            platformStatus: match ($status) {
                OrderStatus::Cancelled => 'rejected',
                OrderStatus::Confirmed => 'approved',
                default => 'pending',
            },
            purchasedAt: Carbon::parse($first['transaction_time'].($first['time_zone'] ?? '+00:00'))->setTimezone(config('app.timezone')),
            items: $items,
            raw: ['transactions' => $rows],
        );
    }

    private function campaignId(Platform $platform): string
    {
        return (string) ($platform->config('campaign_id') ?? config("services.accesstrade.campaigns.{$platform->code}.campaign_id")
            ?? throw AffiliateException::notConfigured($platform->name));
    }

    private function merchant(Platform $platform): string
    {
        return (string) ($platform->config('merchant') ?? config("services.accesstrade.campaigns.{$platform->code}.merchant")
            ?? throw AffiliateException::notConfigured($platform->name));
    }

    private function request(): PendingRequest
    {
        $key = config('services.accesstrade.key');

        if (! $key) {
            throw new AffiliateException('Chưa cấu hình ACCESSTRADE_API_KEY.');
        }

        return Http::baseUrl(config('services.accesstrade.endpoint'))
            ->withHeaders(['Authorization' => "Token {$key}"])
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 500, throw: false);
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->failed() || $response->json('status') === 'fail') {
            throw new AffiliateException('AccessTrade API lỗi: '.($response->json('message') ?? $response->status()));
        }
    }
}
