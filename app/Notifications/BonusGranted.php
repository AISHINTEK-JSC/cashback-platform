<?php

namespace App\Notifications;

use App\Models\UserBonus;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BonusGranted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public UserBonus $bonus) {}

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
        return [
            'title' => 'Bạn nhận được '.mb_strtolower($this->bonus->type->getLabel()),
            'body' => vnd($this->bonus->amount).' đã được cộng vào số dư có thể rút.',
            'url' => route('dashboard.wallet.index'),
        ];
    }
}
