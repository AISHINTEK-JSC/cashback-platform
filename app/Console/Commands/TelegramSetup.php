<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBot;
use Closure;
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

        $commands = collect(TelegramBot::COMMANDS)->map(fn (string $description, string $command) => compact('command', 'description'))->values()->all();
        $description = 'Dán link sản phẩm Shopee, TikTok Shop, Lazada để nhận link hoàn tiền. Nhận thông báo đơn hàng và tiền về ví ngay trên Telegram.';
        $shortDescription = 'Hoàn tiền khi mua sắm online – '.parse_url((string) config('app.url'), PHP_URL_HOST);

        // Mỗi bước độc lập: một bước lỗi (vd. Telegram giới hạn số lần đổi tên) không chặn các bước còn lại
        $steps = [
            'Webhook' => fn () => $bot->call('setWebhook', ['url' => $webhookUrl, 'secret_token' => $bot->webhookSecret(), 'allowed_updates' => ['message']]),
            'Tên hiển thị' => $this->updateIfChanged($bot, 'getMyName', fn (array $result) => $result['name'] ?? null, (string) setting('site_name'), 'setMyName', 'name'),
            'Danh sách lệnh' => $this->updateIfChanged($bot, 'getMyCommands', fn (array $result) => $result, $commands, 'setMyCommands', 'commands'),
            'Mô tả' => $this->updateIfChanged($bot, 'getMyDescription', fn (array $result) => $result['description'] ?? null, $description, 'setMyDescription', 'description'),
            'Mô tả ngắn' => $this->updateIfChanged($bot, 'getMyShortDescription', fn (array $result) => $result['short_description'] ?? null, $shortDescription, 'setMyShortDescription', 'short_description'),
        ];

        $failed = false;

        foreach ($steps as $label => $step) {
            try {
                $this->components->twoColumnDetail($label, $step() === false ? '<fg=gray>không đổi</>' : '<fg=green>đã cập nhật</>');
            } catch (Throwable $e) {
                $failed = true;
                $this->components->twoColumnDetail($label, '<fg=red>lỗi: '.$e->getMessage().'</>');
            }
        }

        if ($failed) {
            $this->components->warn("Thiết lập @{$bot->username()} chưa trọn vẹn, xem các bước lỗi bên trên.");

            return self::FAILURE;
        }

        $this->components->info("Đã thiết lập @{$bot->username()} với webhook {$webhookUrl}.");

        return self::SUCCESS;
    }

    /**
     * Chỉ gọi API set khi giá trị trên Telegram khác giá trị mong muốn; trả false khi không cần đổi.
     *
     * @param  Closure(array<string, mixed>): mixed  $current
     */
    private function updateIfChanged(TelegramBot $bot, string $getMethod, Closure $current, mixed $wanted, string $setMethod, string $param): Closure
    {
        return function () use ($bot, $getMethod, $current, $wanted, $setMethod, $param): bool {
            if ($current((array) ($bot->call($getMethod)['result'] ?? [])) === $wanted) {
                return false;
            }

            $bot->call($setMethod, [$param => $wanted]);

            return true;
        };
    }
}
