<?php

namespace App\Notifications;

use App\Enums\WithdrawalStatus;
use App\Models\Withdrawal;
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
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        $w = $this->withdrawal;

        $body = match ($w->status) {
            WithdrawalStatus::Paid => 'Đã chuyển '.vnd($w->net_amount)." tới tài khoản {$w->bank_name}.",
            WithdrawalStatus::Rejected => "Yêu cầu bị từ chối: {$w->reject_reason}. Tiền đã được hoàn lại số dư khả dụng.",
            default => "Yêu cầu {$w->code}: {$w->status->getLabel()}.",
        };

        return [
            'title' => "Rút tiền {$w->code}: {$w->status->getLabel()}",
            'body' => $body,
            'url' => route('dashboard.withdrawals.index'),
        ];
    }
}
