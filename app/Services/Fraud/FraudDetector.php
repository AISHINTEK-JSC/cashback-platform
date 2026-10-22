<?php

namespace App\Services\Fraud;

use App\Enums\FraudFlagStatus;
use App\Enums\FraudReason;
use App\Enums\OrderStatus;
use App\Models\ActivityLog;
use App\Models\FraudFlag;
use App\Models\User;
use App\Models\UserBankAccount;

/**
 * Phát hiện dấu hiệu gian lận và gắn cờ cho admin xem xét. Không tự khoá tài khoản.
 */
class FraudDetector
{
    public function hasConfirmedFlag(User $user): bool
    {
        return $user->fraudFlags()->where('status', FraudFlagStatus::Confirmed)->exists();
    }

    /**
     * Nhiều nick dùng chung một số tài khoản nhận tiền.
     */
    public function checkSharedBankAccount(UserBankAccount $account): void
    {
        $otherUserIds = UserBankAccount::query()
            ->where('bank_code', $account->bank_code)
            ->where('account_number', $account->account_number)
            ->where('user_id', '!=', $account->user_id)
            ->distinct()
            ->pluck('user_id');

        if ($otherUserIds->isEmpty()) {
            return;
        }

        $this->flag($account->user, FraudReason::SharedBank, min(100, 60 + 10 * $otherUserIds->count()), [
            'bank_code' => $account->bank_code,
            'account_number' => $account->account_number,
            'related_user_ids' => $otherUserIds->all(),
        ]);

        $this->flagSelfReferral($account->user, $otherUserIds->all(), 'shared_bank');
    }

    /**
     * Nhiều nick đăng nhập từ cùng một thiết bị.
     */
    public function checkSharedDevice(User $user, ?string $deviceHash): void
    {
        if (! $deviceHash) {
            return;
        }

        $otherUserIds = ActivityLog::query()
            ->where('device_hash', $deviceHash)
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $user->id)
            ->distinct()
            ->pluck('user_id');

        if ($otherUserIds->isEmpty()) {
            return;
        }

        $this->flag($user, FraudReason::SharedDevice, min(100, 40 + 10 * $otherUserIds->count()), [
            'device_hash' => $deviceHash,
            'related_user_ids' => $otherUserIds->all(),
        ]);

        $this->flagSelfReferral($user, $otherUserIds->all(), 'shared_device');
    }

    /**
     * Tỷ lệ huỷ đơn cao bất thường (mua rồi huỷ để ăn hoa hồng giới thiệu/thưởng).
     */
    public function checkCancelRate(User $user): void
    {
        $total = $user->orders()->count();

        if ($total < (int) setting('fraud_high_cancel_min_orders')) {
            return;
        }

        $cancelled = $user->orders()->where('status', OrderStatus::Cancelled)->count();
        $rate = (int) round($cancelled / $total * 100);

        if ($rate >= (int) setting('fraud_high_cancel_rate')) {
            $this->flag($user, FraudReason::HighCancelRate, $rate, ['orders' => $total, 'cancelled' => $cancelled]);
        }
    }

    /**
     * Người mời và người được mời trùng dấu hiệu nhận dạng => tự giới thiệu chính mình.
     *
     * @param  list<int>  $relatedUserIds
     */
    private function flagSelfReferral(User $user, array $relatedUserIds, string $signal): void
    {
        $isReferralPair = ($user->referred_by && in_array($user->referred_by, $relatedUserIds, true))
            || User::query()->whereIn('id', $relatedUserIds)->where('referred_by', $user->id)->exists();

        if ($isReferralPair) {
            $this->flag($user, FraudReason::SelfReferral, 90, ['signal' => $signal, 'related_user_ids' => $relatedUserIds]);
        }
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function flag(User $user, FraudReason $reason, int $score, array $detail): void
    {
        $existing = $user->fraudFlags()->where('reason', $reason)->where('status', FraudFlagStatus::Open)->first();

        if ($existing) {
            $existing->update(['score' => max($existing->score, $score), 'detail' => $detail]);

            return;
        }

        FraudFlag::query()->create([
            'user_id' => $user->id,
            'reason' => $reason,
            'score' => $score,
            'detail' => $detail,
            'status' => FraudFlagStatus::Open,
        ]);
    }
}
