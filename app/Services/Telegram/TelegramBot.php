<?php

namespace App\Services\Telegram;

use App\Support\Settings;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Gọi Telegram Bot API. Token lấy từ Cài đặt tích hợp (lưu mã hoá), chưa nhập thì dùng TELEGRAM_BOT_TOKEN trong .env.
 */
class TelegramBot
{
    private const API_URL = 'https://api.telegram.org/bot';

    /**
     * Danh sách lệnh hiện trong menu của bot (setMyCommands).
     *
     * @var array<string, string>
     */
    public const COMMANDS = [
        'start' => 'Bắt đầu và liên kết tài khoản Ví Hoàn Tiền',
        'taolink' => 'Tạo link hoàn tiền từ link sản phẩm',
        'sodu' => 'Xem số dư ví và tiền đang chờ duyệt',
        'donhang' => 'Xem đơn hàng gần đây',
        'ruttien' => 'Rút tiền về tài khoản ngân hàng',
        'gioithieu' => 'Lấy link giới thiệu bạn bè',
        'thongbao' => 'Bật/tắt nhận thông báo qua Telegram',
        'trogiup' => 'Hướng dẫn sử dụng và liên hệ hỗ trợ',
        'huylienket' => 'Huỷ liên kết tài khoản với Telegram',
    ];

    public function __construct(private readonly Settings $settings) {}

    public function isConfigured(): bool
    {
        return filled($this->token());
    }

    public function username(): string
    {
        return (string) config('services.telegram.bot_username');
    }

    /**
     * Link mở bot kèm mã liên kết một lần: t.me/<bot>?start=<mã>.
     */
    public function startUrl(?string $payload = null): string
    {
        return 'https://t.me/'.$this->username().($payload ? '?start='.$payload : '');
    }

    /**
     * Mã bí mật Telegram gửi kèm mỗi request webhook (header X-Telegram-Bot-Api-Secret-Token), suy ra từ token và APP_KEY.
     */
    public function webhookSecret(): string
    {
        return hash_hmac('sha256', 'telegram-webhook:'.$this->token(), (string) config('app.key'));
    }

    /**
     * @param  list<array{0: string, 1: string}>  $buttons  mỗi nút một hàng: [nhãn, url]
     */
    public function sendMessage(int|string $chatId, string $html, array $buttons = []): void
    {
        $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
            'reply_markup' => $buttons === [] ? null : [
                'inline_keyboard' => array_map(fn (array $button) => [['text' => $button[0], 'url' => $button[1]]], $buttons),
            ],
        ]));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function call(string $method, array $params = []): array
    {
        return Http::timeout(10)
            ->post(self::API_URL.$this->token().'/'.$method, $params)
            ->throw()
            ->json() ?? [];
    }

    private function token(): ?string
    {
        return $this->settings->secret('telegram_bot_token');
    }
}
