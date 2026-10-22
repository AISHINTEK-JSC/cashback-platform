<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\User;
use App\Models\UserLevel;

/**
 * Xếp hạng thành viên theo số đơn đã duyệt.
 */
class UserLevelService
{
    public function approvedOrderCount(User $user): int
    {
        return $user->orders()->whereIn('status', [OrderStatus::Approved, OrderStatus::Paid])->count();
    }

    public function recalculate(User $user): ?UserLevel
    {
        $level = UserLevel::query()
            ->where('min_approved_orders', '<=', $this->approvedOrderCount($user))
            ->orderByDesc('min_approved_orders')
            ->first();

        if ($user->level_id !== $level?->id) {
            $user->forceFill(['level_id' => $level?->id])->save();
        }

        return $level;
    }

    public function nextLevel(User $user): ?UserLevel
    {
        return UserLevel::query()
            ->where('min_approved_orders', '>', $this->approvedOrderCount($user))
            ->orderBy('min_approved_orders')
            ->first();
    }
}
