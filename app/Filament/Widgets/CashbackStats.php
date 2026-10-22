<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Order;
use App\Models\OrderReward;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashbackStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();

        $validOrders = Order::query()
            ->where('purchased_at', '>=', $monthStart)
            ->whereNotIn('status', [OrderStatus::Cancelled]);

        $commission = (int) (clone $validOrders)->sum('commission_amount');
        $orderIds = (clone $validOrders)->select('id');
        $paidOut = (int) OrderReward::query()->whereIn('order_id', $orderIds)->where('status', '!=', 'cancelled')->sum('amount');

        $pendingWithdrawals = Withdrawal::query()->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing]);

        return [
            Stat::make('Hoa hồng tháng này', vnd($commission))
                ->description((clone $validOrders)->count().' đơn hợp lệ'),
            Stat::make('Chia cho thành viên (gồm F1/F2)', vnd($paidOut))
                ->description($commission > 0 ? round($paidOut / $commission * 100, 1).'% hoa hồng' : '-'),
            Stat::make('Lợi nhuận giữ lại', vnd($commission - $paidOut))
                ->color('success'),
            Stat::make('Rút tiền cần xử lý', vnd((int) (clone $pendingWithdrawals)->sum('amount')))
                ->description((clone $pendingWithdrawals)->count().' yêu cầu')
                ->color('warning'),
            Stat::make('Đơn chưa khớp thành viên', (string) Order::query()->whereNull('user_id')->where('purchased_at', '>=', now()->subDays(30))->count())
                ->description('30 ngày qua'),
            Stat::make('Thành viên mới tháng này', (string) User::query()->where('created_at', '>=', $monthStart)->count()),
        ];
    }
}
