<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Fraud\FraudDetector;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fraud:scan')]
#[Description('Quét user có đơn phát sinh trong 30 ngày để phát hiện tỷ lệ huỷ bất thường')]
class ScanFraud extends Command
{
    public function handle(FraudDetector $detector): int
    {
        User::query()
            ->whereHas('orders', fn ($q) => $q->where('updated_at', '>=', now()->subDays(30)))
            ->chunkById(500, fn ($users) => $users->each(fn (User $user) => $detector->checkCancelRate($user)));

        $this->components->info('Đã quét xong.');

        return self::SUCCESS;
    }
}
