<?php

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\ReferralService;
use App\Services\UserLevelService;

/**
 * Tạo tài khoản thành viên (đăng ký thường hoặc qua Google/Facebook) và gắn người giới thiệu.
 */
class RegisterUser
{
    public function __construct(
        private readonly ReferralService $referrals,
        private readonly UserLevelService $levels,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: string|null, password?: string|null, avatar?: string|null, email_verified_at?: mixed}  $attributes
     */
    public function handle(array $attributes, ?string $referralCode = null): User
    {
        $referrer = $this->referrals->findReferrer($referralCode);

        $user = User::query()->create([
            ...$attributes,
            'referred_by' => $referrer?->isActive() ? $referrer->id : null,
            'status' => UserStatus::Active,
        ]);

        $this->levels->recalculate($user);

        return $user;
    }
}
