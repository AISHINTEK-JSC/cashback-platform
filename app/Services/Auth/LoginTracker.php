<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Fraud\FraudDetector;
use Illuminate\Http\Request;

/**
 * Ghi lại đăng nhập/đăng ký: thời điểm, IP, thiết bị, rồi kiểm tra dùng chung thiết bị.
 */
class LoginTracker
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly FraudDetector $fraud,
        private readonly Request $request,
    ) {}

    public function record(User $user, string $action = 'login'): void
    {
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $this->request->ip()])->save();

        $this->activity->log($user, $action);
        $this->fraud->checkSharedDevice($user, $this->activity->deviceHash());
    }
}
