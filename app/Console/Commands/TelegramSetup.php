<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('telegram:setup')]
#[Description('Đăng ký webhook, tên hiển thị, danh sách lệnh và mô tả cho bot Telegram (chạy ở production sau mỗi lần deploy)')]
class TelegramSetup extends Command
{
    public function handle(TelegramBot $bot): int
    {
        if (! $bot->isConfigured()) {
            $this->components->warn('Chưa có Telegram Bot token, bỏ qua.');

            return self::SUCCESS;
        }

        $webhookUrl = route('telegram.webhook');

        // Local dùng chung token với production: đăng ký webhook về localhost sẽ làm bot production ngừng nhận tin
        if (! str_starts_with($webhookUrl, 'https://') || str_contains($webhookUrl, 'localhost')) {
            $this->components->error("Webhook phải là HTTPS công khai, hiện là {$webhookUrl}. Chỉ chạy lệnh này ở production.");

            return self::FAILURE;
        }

        try {
            $bot->call('setWebhook', [
                'url' => $webhookUrl,
                'secret_token' => $bot->webhookSecret(),
                'allowed_updates' => ['message'],
            ]);
            $bot->call('setMyName', ['name' => setting('site_name')]);
            $bot->call('setMyCommands', [
                'commands' => collect(TelegramBot::COMMANDS)->map(fn (string $description, string $command) => compact('command', 'description'))->values()->all(),
            ]);
            $bot->call('setMyDescription', ['description' => 'Dán link sản phẩm Shopee, TikTok Shop, Lazada để nhận link hoàn tiền. Nhận thông báo đơn hàng và tiền về ví ngay trên Telegram.']);
            $bot->call('setMyShortDescription', ['short_description' => 'Hoàn tiền khi mua sắm online – '.parse_url((string) config('app.url'), PHP_URL_HOST)]);
        } catch (Throwable $e) {
            $this->components->error('Không thiết lập được bot: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Đã đăng ký webhook {$webhookUrl} và danh sách lệnh cho @{$bot->username()}.");

        return self::SUCCESS;
    }
}
