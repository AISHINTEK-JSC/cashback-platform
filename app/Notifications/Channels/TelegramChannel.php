<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\Telegram\TelegramBot;
use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Gửi thông báo qua bot Telegram cho thành viên đã liên kết, dùng chung title/body/url của toArray().
 * Gửi lỗi không được làm hỏng luồng chính (đồng bộ đơn, duyệt rút tiền), user chặn bot thì tự gỡ liên kết.
 */
class TelegramChannel
{
    public function __construct(private readonly TelegramBot $bot) {}

    /**
     * Dùng trong via(): ['database', ...TelegramChannel::for($notifiable)].
     *
     * @return list<class-string<self>>
     */
    public static function for(object $notifiable): array
    {
        return static::wantsNotifications($notifiable) ? [self::class] : [];
    }

    public function send(object $notifiable, Notification $notification): void
    {
        // Kiểm tra lại lúc gửi: thông báo đi qua hàng đợi, user có thể đã tắt hoặc huỷ liên kết trong lúc chờ
        if (! static::wantsNotifications($notifiable) || ! $this->bot->isConfigured() || ! method_exists($notification, 'toArray')) {
            return;
        }

        $chatId = $notifiable->telegram_chat_id;

        $data = $notification->toArray($notifiable);

        try {
            $this->bot->sendMessage(
                $chatId,
                '🔔 <b>'.e($data['title'] ?? 'Thông báo').'</b>'.(filled($data['body'] ?? null) ? "\n".e($data['body']) : ''),
                filled($data['url'] ?? null) ? [['Xem chi tiết', $data['url']]] : [],
            );
        } catch (RequestException $e) {
            if ($e->response->status() === 403 && $notifiable instanceof User) {
                $notifiable->update(['telegram_chat_id' => null, 'telegram_username' => null, 'telegram_linked_at' => null]);

                return;
            }

            report($e);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Đã liên kết Telegram và không tắt "Nhận thông báo qua Telegram" ở trang Hồ sơ hoặc bằng /thongbao.
     */
    private static function wantsNotifications(object $notifiable): bool
    {
        return filled($notifiable->telegram_chat_id ?? null) && ($notifiable->telegram_notifications ?? true);
    }
}
