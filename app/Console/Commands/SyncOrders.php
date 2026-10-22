<?php

namespace App\Console\Commands;

use App\Enums\SyncStatus;
use App\Models\Platform;
use App\Models\SyncLog;
use App\Services\Orders\OrderSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('orders:sync {--platform= : Mã sàn, bỏ trống = tất cả sàn đang bật} {--full : Quét lại toàn bộ khoảng lookback để cập nhật trạng thái đơn cũ}')]
#[Description('Kéo đơn hàng từ API affiliate của các sàn')]
class SyncOrders extends Command
{
    public function handle(OrderSyncService $sync): int
    {
        $platforms = Platform::query()->active()
            ->when($this->option('platform'), fn ($q, $code) => $q->where('code', $code))
            ->get();

        foreach ($platforms as $platform) {
            $to = now();
            $from = $this->windowStart($platform);

            $log = $sync->sync($platform, $from, $to);

            $this->components->twoColumnDetail(
                "{$platform->name} ({$from->format('d/m H:i')} → {$to->format('d/m H:i')})",
                $log->status === SyncStatus::Success
                    ? "<fg=green>{$log->fetched} đơn, +{$log->inserted} mới, {$log->updated} cập nhật</>"
                    : "<fg=red>Lỗi: {$log->error}</>",
            );
        }

        return self::SUCCESS;
    }

    private function windowStart(Platform $platform): Carbon
    {
        $lookbackStart = now()->subDays((int) config('cashback.sync_lookback_days'));

        if ($this->option('full')) {
            return $lookbackStart;
        }

        $lastWindowEnd = SyncLog::query()
            ->where('platform_id', $platform->id)
            ->where('status', SyncStatus::Success)
            ->max('window_to');

        if (! $lastWindowEnd) {
            return $lookbackStart;
        }

        return Carbon::parse($lastWindowEnd)->subMinutes((int) config('cashback.sync_overlap_minutes'));
    }
}
