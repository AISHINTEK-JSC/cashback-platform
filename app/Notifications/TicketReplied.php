<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketReplied extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

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
            'title' => 'Hỗ trợ đã trả lời',
            'body' => "Yêu cầu \"{$this->ticket->subject}\" có phản hồi mới.",
            'url' => route('dashboard.tickets.show', $this->ticket),
        ];
    }
}
