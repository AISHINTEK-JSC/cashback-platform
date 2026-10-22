<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
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
        $label = $this->order->platform->name.' '.$this->order->platform_order_id;

        [$title, $body] = match ($this->order->status) {
            OrderStatus::Approved => ['Tiền hoàn đã có thể rút', "Đơn {$label} đã được duyệt, ".vnd($this->order->cashback_amount).' đã chuyển vào số dư khả dụng.'],
            OrderStatus::Cancelled => ['Đơn hàng bị huỷ', "Đơn {$label} đã bị huỷ hoặc hoàn hàng nên không được hoàn tiền."],
            default => ['Đơn hàng cập nhật trạng thái', "Đơn {$label}: {$this->order->status->getLabel()}."],
        };

        return [
            'title' => $title,
            'body' => $body,
            'url' => route('dashboard.orders.show', $this->order),
        ];
    }
}
