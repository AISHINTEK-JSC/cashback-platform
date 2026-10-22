<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderRecorded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Đơn hàng đã được ghi nhận',
            'body' => sprintf('Đơn %s %s được ghi nhận, hoàn dự kiến %s.', $this->order->platform->name, $this->order->platform_order_id, vnd($this->order->cashback_amount)),
            'url' => route('dashboard.orders.show', $this->order),
        ];
    }
}
