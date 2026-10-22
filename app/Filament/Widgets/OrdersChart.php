<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrdersChart extends ChartWidget
{
    protected ?string $heading = 'Đơn hàng và hoa hồng 30 ngày';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $from = now()->subDays(29)->startOfDay();

        $rows = Order::query()
            ->where('purchased_at', '>=', $from)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->get(['purchased_at', 'commission_amount'])
            ->groupBy(fn (Order $o) => $o->purchased_at->format('Y-m-d'));

        $days = collect(range(0, 29))->map(fn (int $i) => $from->copy()->addDays($i));

        return [
            'datasets' => [
                [
                    'label' => 'Số đơn',
                    'data' => $days->map(fn (Carbon $d) => $rows->get($d->format('Y-m-d'))?->count() ?? 0)->all(),
                    'borderColor' => '#e04412',
                    'backgroundColor' => 'rgba(224, 68, 18, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Hoa hồng (nghìn đ)',
                    'data' => $days->map(fn (Carbon $d) => round(($rows->get($d->format('Y-m-d'))?->sum('commission_amount') ?? 0) / 1000))->all(),
                    'borderColor' => '#0f766e',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.1)',
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $days->map(fn (Carbon $d) => $d->format('d/m'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['position' => 'left', 'beginAtZero' => true],
                'y1' => ['position' => 'right', 'beginAtZero' => true, 'grid' => ['drawOnChartArea' => false]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
