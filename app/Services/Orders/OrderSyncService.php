<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderStatusSource;
use App\Enums\SyncStatus;
use App\Models\Order;
use App\Models\Platform;
use App\Models\SyncLog;
use App\Models\TrackingLink;
use App\Notifications\OrderRecorded;
use App\Services\Affiliate\AffiliateManager;
use App\Services\Affiliate\Data\PlatformOrderData;
use App\Services\Affiliate\Data\PlatformOrderItemData;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Kéo báo cáo đơn từ API sàn và đưa về bảng orders. Chạy lại bao nhiêu lần cũng cho cùng kết quả.
 */
class OrderSyncService
{
    public function __construct(
        private readonly AffiliateManager $affiliates,
        private readonly OrderRewardService $rewards,
        private readonly OrderStatusService $statuses,
    ) {}

    public function sync(Platform $platform, CarbonInterface $from, CarbonInterface $to): SyncLog
    {
        $log = SyncLog::query()->create([
            'platform_id' => $platform->id,
            'window_from' => $from,
            'window_to' => $to,
            'status' => SyncStatus::Running,
            'started_at' => now(),
        ]);

        $counts = ['fetched' => 0, 'inserted' => 0, 'updated' => 0];

        try {
            foreach ($this->affiliates->driver($platform)->fetchOrders($platform, $from, $to) as $data) {
                $counts['fetched']++;
                $result = $this->upsert($platform, $data);

                if ($result !== 'unchanged') {
                    $counts[$result]++;
                }
            }

            $log->update([...$counts, 'status' => SyncStatus::Success, 'finished_at' => now()]);
        } catch (Throwable $e) {
            $log->update([...$counts, 'status' => SyncStatus::Failed, 'error' => $e->getMessage(), 'finished_at' => now()]);
            report($e);
        }

        return $log;
    }

    /**
     * @return 'inserted'|'updated'|'unchanged'
     */
    public function upsert(Platform $platform, PlatformOrderData $data): string
    {
        [$result, $order, $isNew] = DB::transaction(function () use ($platform, $data): array {
            $order = Order::query()
                ->where('platform_id', $platform->id)
                ->where('platform_order_id', $data->platformOrderId)
                ->lockForUpdate()
                ->first();

            $isNew = $order === null;
            $order ??= $this->newOrder($platform, $data);

            $commissionChanged = ! $isNew && $order->commission_amount !== $data->commissionAmount;

            $order->fill([
                'order_amount' => $data->orderAmount,
                'commission_amount' => $data->commissionAmount,
                'platform_status' => $data->platformStatus,
                'raw_payload' => $data->raw,
                'last_synced_at' => now(),
            ]);

            $dirty = $order->isDirty(['order_amount', 'commission_amount', 'platform_status']);
            $order->save();

            if ($isNew || $dirty) {
                $this->syncItems($order, $data->items);
            }

            if ($isNew) {
                $order->statusHistories()->create(['to_status' => OrderStatus::Pending->value, 'source' => OrderStatusSource::Sync, 'note' => 'Ghi nhận đơn']);
                $this->rewards->createRewards($order);
            } elseif ($commissionChanged && in_array($order->status, [OrderStatus::Pending, OrderStatus::Confirmed], true)) {
                $this->rewards->recalculate($order);
            }

            $transitioned = $this->applyPlatformStatus($order, $data->status);

            return [$isNew ? 'inserted' : ($dirty || $transitioned ? 'updated' : 'unchanged'), $order, $isNew];
        });

        if ($isNew && $order->user && $order->status !== OrderStatus::Cancelled) {
            $order->user->notify(new OrderRecorded($order));
        }

        return $result;
    }

    private function newOrder(Platform $platform, PlatformOrderData $data): Order
    {
        $link = $data->subId ? TrackingLink::query()->where('sub_id', $data->subId)->first() : null;

        $order = new Order([
            'platform_id' => $platform->id,
            'platform_order_id' => $data->platformOrderId,
            'sub_id' => $data->subId,
            'user_id' => $link?->user_id,
            'tracking_link_id' => $link?->id,
            'cashback_amount' => 0,
            'status' => OrderStatus::Pending,
            'purchased_at' => $data->purchasedAt,
        ]);

        $order->setRelation('platform', $platform);

        return $order;
    }

    /**
     * Sync chỉ đẩy trạng thái đi tiến hoặc sang huỷ; không kéo lùi đơn hệ thống đã duyệt.
     */
    private function applyPlatformStatus(Order $order, OrderStatus $platformStatus): bool
    {
        if ($platformStatus === OrderStatus::Pending) {
            return false;
        }

        return $this->statuses->transition($order, $platformStatus, OrderStatusSource::Sync, 'Trạng thái sàn: '.$order->platform_status);
    }

    /**
     * @param  list<PlatformOrderItemData>  $items
     */
    private function syncItems(Order $order, array $items): void
    {
        $order->items()->delete();

        $order->items()->createMany(array_map(fn (PlatformOrderItemData $item) => [
            'item_id' => $item->itemId,
            'item_name' => $item->itemName,
            'category_code' => $item->categoryCode,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'commission_rate' => $item->commissionRate,
            'commission_amount' => $item->commissionAmount,
            'status' => $item->status,
        ], $items));

        $order->unsetRelation('items');
    }
}
