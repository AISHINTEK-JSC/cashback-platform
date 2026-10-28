<?php

namespace App\Notifications;

use App\Enums\WithdrawalMethod;
use App\Enums\WithdrawalStatus;
use App\Models\Withdrawal;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WithdrawalStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Withdrawal $withdrawal) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', ...TelegramChannel::for($notifiable)];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        $w = $this->withdrawal;

        // Không đưa mã thẻ cào vào thông báo: nội dung được lưu thô trong bảng notifications và gửi qua Telegram
        $body = match (true) {
            $w->status === WithdrawalStatus::Paid && $w->method === WithdrawalMethod::PhoneCard => "Thẻ cào {$w->phone_carrier?->getLabel()} ".vnd($w->net_amount).' đã sẵn sàng. Xem serial và mã thẻ ở mục Rút tiền.',
            $w->status === WithdrawalStatus::Paid && $w->method === WithdrawalMethod::PhoneTopup => 'Đã nạp '.vnd($w->net_amount)." vào số {$w->phone_number} ({$w->phone_carrier?->getLabel()}).",
            $w->status === WithdrawalStatus::Paid => 'Đã chuyển '.vnd($w->net_amount)." tới tài khoản {$w->bank_name}.",
            $w->status === WithdrawalStatus::Rejected => "Yêu cầu bị từ chối: {$w->reject_reason}. Tiền đã được hoàn lại số dư khả dụng.",
            default => "Yêu cầu {$w->code}: {$w->status->getLabel()}.",
        };

        return [
            'title' => "Rút tiền {$w->code}: {$w->status->getLabel()}",
            'body' => $body,
            'url' => route('dashboard.withdrawals.index'),
        ];
    }
}
