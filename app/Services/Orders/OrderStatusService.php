<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderStatusSource;
use App\Models\Order;
use App\Notifications\OrderStatusChanged;
use App\Services\NewMemberBonusService;
use App\Services\UserLevelService;
use Illuminate\Support\Facades\DB;

/**
 * Chuyển trạng thái đơn, ghi lịch sử và kích hoạt các thay đổi về tiền đi kèm.
 */
class OrderStatusService
{
    public function __construct(
        private readonly OrderRewardService $rewards,
        private readonly UserLevelService $levels,
        private readonly NewMemberBonusService $bonuses,
    ) {}

    public function transition(Order $order, OrderStatus $to, OrderStatusSource $source, ?string $note = null): bool
    {
        $from = $order->status;

        if ($from === $to || ! $from->canTransitionTo($to)) {
            return false;
        }

        DB::transaction(function () use ($order, $from, $to, $source, $note): void {
            $timestampColumn = match ($to) {
                OrderStatus::Confirmed => 'confirmed_at',
                OrderStatus::Approved => 'approved_at',
                OrderStatus::Cancelled => 'cancelled_at',
                default => null,
            };

            $order->status = $to;

            if ($timestampColumn) {
                $order->{$timestampColumn} = now();
            }

            if ($to === OrderStatus::Approved && ! $order->confirmed_at) {
                $order->confirmed_at = now();
            }

            $order->save();

            $order->statusHistories()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'source' => $source,
                'note' => $note,
            ]);

            if ($to === OrderStatus::Approved) {
                $this->rewards->release($order);
                $this->bonuses->grantFirstOrderBonus($order);
            }

            if ($to === OrderStatus::Cancelled) {
                $this->rewards->cancel($order, $note ?? 'Đơn bị huỷ');
                $this->bonuses->revokeFirstOrderBonus($order);
            }
        });

        if ($order->user && in_array($to, [OrderStatus::Approved, OrderStatus::Cancelled], true)) {
            $this->levels->recalculate($order->user);
            $order->user->notify(new OrderStatusChanged($order));
        }

        return true;
    }

    /**
     * Duyệt các đơn sàn đã xác nhận và đã qua số ngày chờ chốt của sàn.
     */
    public function approveDueOrders(): int
    {
        $approved = 0;

        Order::query()
            ->with('platform')
            ->where('status', OrderStatus::Confirmed)
            ->join('platforms', 'platforms.id', '=', 'orders.platform_id')
            ->select('orders.*')
            ->whereRaw($this->dueCondition())
            ->chunkById(200, function ($orders) use (&$approved): void {
                foreach ($orders as $order) {
                    if ($this->transition($order, OrderStatus::Approved, OrderStatusSource::System, 'Hết thời gian chờ sàn chốt đơn')) {
                        $approved++;
                    }
                }
            }, 'orders.id', 'id');

        return $approved;
    }

    private function dueCondition(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "datetime(orders.purchased_at, '+' || platforms.lock_days || ' days') <= datetime('now')",
            'pgsql' => 'orders.purchased_at + make_interval(days => platforms.lock_days) <= NOW()',
            default => 'DATE_ADD(orders.purchased_at, INTERVAL platforms.lock_days DAY) <= NOW()',
        };
    }
}
