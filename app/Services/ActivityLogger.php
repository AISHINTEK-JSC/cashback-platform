<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Ghi nhật ký hoạt động kèm dấu vân tay thiết bị (cookie lâu dài) để phát hiện multi-account.
 */
class ActivityLogger
{
    public const DEVICE_COOKIE = 'did';

    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function log(?User $user, string $action, array $meta = []): ActivityLog
    {
        return ActivityLog::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'ip' => $this->request->ip(),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 250, ''),
            'device_hash' => $this->deviceHash(),
            'meta' => $meta ?: null,
        ]);
    }

    public function deviceHash(): ?string
    {
        $deviceId = $this->request->cookie(self::DEVICE_COOKIE);

        return $deviceId ? hash('sha256', $deviceId) : null;
    }
}
