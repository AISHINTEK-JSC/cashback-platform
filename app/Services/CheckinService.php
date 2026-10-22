<?php

namespace App\Services;

use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Exceptions\BusinessRuleException;
use App\Models\Checkin;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Điểm danh hằng ngày, thưởng tăng dần theo chuỗi ngày liên tiếp.
 */
class CheckinService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly Settings $settings,
    ) {}

    public function hasCheckedInToday(User $user): bool
    {
        return $user->checkins()->whereDate('checkin_date', today())->exists();
    }

    public function currentStreak(User $user): int
    {
        $last = $user->checkins()->latest('checkin_date')->first();

        if (! $last || $last->checkin_date->lt(today()->subDay())) {
            return 0;
        }

        return $last->streak;
    }

    /**
     * Thưởng cho ngày thứ $streak trong chuỗi (lặp lại theo chu kỳ cấu hình).
     */
    public function rewardFor(int $streak): int
    {
        $rewards = $this->settings->checkinRewards();

        if ($rewards === []) {
            return 0;
        }

        return $rewards[($streak - 1) % count($rewards)];
    }

    public function checkin(User $user): Checkin
    {
        try {
            return DB::transaction(function () use ($user): Checkin {
                $streak = $this->currentStreak($user) + 1;

                $checkin = $user->checkins()->create([
                    'checkin_date' => today(),
                    'streak' => $streak,
                    'reward_amount' => $this->rewardFor($streak),
                ]);

                if ($checkin->reward_amount > 0) {
                    $this->wallet->post($user, WalletTransactionType::Checkin, WalletBucket::Available, $checkin->reward_amount, $checkin, "Điểm danh ngày thứ {$streak}");
                }

                return $checkin;
            });
        } catch (UniqueConstraintViolationException) {
            throw new BusinessRuleException('Hôm nay bạn đã điểm danh rồi.');
        }
    }
}
