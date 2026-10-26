<?php

namespace App\Filament\Pages;

use App\Filament\Support\NavigationGroup;
use App\Services\ActivityLogger;
use BackedEnum;
use Database\Seeders\BlogPostSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;
use UnitEnum;

/**
 * Chạy các lệnh artisan định sẵn từ trang quản trị, thay cho shell trên host không có quyền truy cập.
 *
 * Chỉ cho chọn trong danh sách COMMANDS, không nhận lệnh tự do: tài khoản admin bị lộ
 * cũng không thể chạy lệnh phá dữ liệu như migrate:fresh, db:wipe.
 */
class RunCommands extends Page
{
    /**
     * @var array<string, array{label: string, description: string, command: string, parameters: array<string, mixed>}>
     */
    public const COMMANDS = [
        'blog-posts' => [
            'label' => 'Đăng bộ bài viết có sẵn',
            'description' => 'Tạo các bài blog SEO soạn sẵn còn thiếu. Bài đã có (theo đường dẫn) được giữ nguyên, không ghi đè bài đã sửa.',
            'command' => 'db:seed',
            'parameters' => ['--class' => BlogPostSeeder::class, '--force' => true],
        ],
        'reference-data' => [
            'label' => 'Tạo dữ liệu nền',
            'description' => 'Tạo hạng thành viên, sàn và tỷ lệ chia mặc định nếu chưa có. Không sửa dữ liệu đang dùng.',
            'command' => 'db:seed',
            'parameters' => ['--class' => ReferenceDataSeeder::class, '--force' => true],
        ],
        'orders-sync' => [
            'label' => 'Đồng bộ đơn hàng',
            'description' => 'Kéo đơn mới và cập nhật trạng thái đơn gần đây từ API các sàn.',
            'command' => 'orders:sync',
            'parameters' => [],
        ],
        'orders-sync-full' => [
            'label' => 'Đồng bộ lại đơn 60 ngày',
            'description' => 'Quét lại toàn bộ đơn trong 60 ngày để cập nhật trạng thái đơn cũ. Có thể mất vài phút.',
            'command' => 'orders:sync',
            'parameters' => ['--full' => true],
        ],
        'orders-approve-due' => [
            'label' => 'Duyệt đơn đến hạn',
            'description' => 'Chuyển tiền của các đơn đã hết thời gian chờ sang số dư khả dụng.',
            'command' => 'orders:approve-due',
            'parameters' => [],
        ],
        'fraud-scan' => [
            'label' => 'Quét gian lận',
            'description' => 'Gắn cờ tài khoản có dấu hiệu gian lận theo cấu hình trong Cài đặt chung.',
            'command' => 'fraud:scan',
            'parameters' => [],
        ],
        'queue-work' => [
            'label' => 'Xử lý hàng đợi',
            'description' => 'Gửi các thông báo, email đang chờ trong hàng đợi rồi dừng.',
            'command' => 'queue:work',
            'parameters' => ['--stop-when-empty' => true, '--max-time' => 50, '--tries' => 3],
        ],
        'cache-clear' => [
            'label' => 'Xoá cache dữ liệu',
            'description' => 'Làm mới cài đặt, thống kê trang chủ, sitemap. Dùng khi sửa trực tiếp dữ liệu mà website chưa cập nhật.',
            'command' => 'cache:clear',
            'parameters' => [],
        ],
        'migrate-status' => [
            'label' => 'Xem trạng thái migration',
            'description' => 'Liệt kê các migration đã chạy và chưa chạy. Chỉ đọc, không thay đổi database.',
            'command' => 'migrate:status',
            'parameters' => [],
        ],
    ];

    protected string $view = 'filament.pages.run-commands';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Chạy lệnh';

    protected static ?string $title = 'Chạy lệnh hệ thống';

    /**
     * Kết quả lần chạy gần nhất để hiển thị dưới danh sách lệnh.
     *
     * @var array{label: string, command_line: string, exit_code: int, output: string, duration: float, ran_at: string}|null
     */
    public ?array $lastRun = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function commandLine(string $key): string
    {
        $definition = self::definition($key);

        $options = collect($definition['parameters'])->map(fn (mixed $value, string $option): string => match (true) {
            $value === true => $option,
            $option === '--class' => $option.'='.class_basename((string) $value),
            default => "{$option}={$value}",
        });

        return trim('php artisan '.$definition['command'].' '.$options->join(' '));
    }

    public function runAction(): Action
    {
        return Action::make('run')
            ->label('Chạy')
            ->icon(Heroicon::OutlinedPlay)
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedCommandLine)
            ->modalHeading(fn (array $arguments): string => self::definition($arguments['command'] ?? '')['label'])
            ->modalDescription(fn (array $arguments): string => self::commandLine($arguments['command'] ?? ''))
            ->modalSubmitActionLabel('Chạy ngay')
            ->action(fn (array $arguments, ActivityLogger $activity) => $this->run($arguments['command'] ?? '', $activity));
    }

    private function run(string $key, ActivityLogger $activity): void
    {
        $definition = self::definition($key);
        $lock = Cache::lock("admin-command:{$key}", 600);

        if (! $lock->get()) {
            Notification::make()->title('Lệnh này đang chạy, vui lòng đợi xong rồi thử lại.')->warning()->send();

            return;
        }

        $output = new BufferedOutput;
        $startedAt = microtime(true);

        try {
            set_time_limit(300);
            $exitCode = Artisan::call($definition['command'], $definition['parameters'], $output);
        } catch (Throwable $e) {
            report($e);
            $exitCode = 1;
            $output->writeln($e::class.': '.$e->getMessage());
        } finally {
            $lock->release();
        }

        $this->lastRun = [
            'label' => $definition['label'],
            'command_line' => self::commandLine($key),
            'exit_code' => $exitCode,
            'output' => trim($output->fetch()) ?: '(Lệnh không in ra gì)',
            'duration' => round(microtime(true) - $startedAt, 1),
            'ran_at' => now()->format('H:i:s d/m/Y'),
        ];

        $activity->log(auth()->user(), 'admin.command', ['command' => $key, 'exit_code' => $exitCode]);

        Notification::make()
            ->title($exitCode === 0 ? "Đã chạy xong: {$definition['label']}" : "Lệnh lỗi: {$definition['label']}")
            ->status($exitCode === 0 ? 'success' : 'danger')
            ->send();
    }

    /**
     * @return array{label: string, description: string, command: string, parameters: array<string, mixed>}
     */
    private static function definition(string $key): array
    {
        abort_unless(array_key_exists($key, self::COMMANDS), 404);

        return self::COMMANDS[$key];
    }
}
