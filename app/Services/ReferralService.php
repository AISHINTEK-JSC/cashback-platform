<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cây giới thiệu 2 tầng: F1 = người mình mời trực tiếp, F2 = người F1 mời.
 */
class ReferralService
{
    public const COOKIE = 'ref';

    public function findReferrer(?string $code): ?User
    {
        if (! $code) {
            return null;
        }

        return User::query()->where('referral_code', strtoupper(trim($code)))->first();
    }

    /**
     * @return array{f1_count: int, f2_count: int, f1_earned: int, f2_earned: int}
     */
    public function summary(User $user): array
    {
        $earned = DB::table('order_rewards')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->whereIn('type', ['f1', 'f2'])
            ->groupBy('type')
            ->selectRaw('type, SUM(amount) as total')
            ->pluck('total', 'type');

        return [
            'f1_count' => $user->referrals()->count(),
            'f2_count' => $user->secondLevelReferrals()->count(),
            'f1_earned' => (int) ($earned['f1'] ?? 0),
            'f2_earned' => (int) ($earned['f2'] ?? 0),
        ];
    }

    /**
     * Hoa hồng user nhận được từ từng F1 (bao gồm cả F2 dưới F1 đó).
     *
     * @return Collection<int, int>
     */
    public function earningsByReferral(User $user): Collection
    {
        return DB::table('order_rewards')
            ->join('orders', 'orders.id', '=', 'order_rewards.order_id')
            ->join('users as buyers', 'buyers.id', '=', 'orders.user_id')
            ->where('order_rewards.user_id', $user->id)
            ->where('order_rewards.status', '!=', 'cancelled')
            ->whereIn('order_rewards.type', ['f1', 'f2'])
            ->selectRaw("CASE WHEN order_rewards.type = 'f1' THEN buyers.id ELSE buyers.referred_by END as f1_id, SUM(order_rewards.amount) as total")
            ->groupBy('f1_id')
            ->pluck('total', 'f1_id')
            ->map(fn ($total) => (int) $total);
    }
}
