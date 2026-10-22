<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use App\Services\ReferralService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gắn cookie định danh thiết bị (phục vụ chống multi-account) và ghi nhớ mã giới thiệu từ ?ref=.
 */
class TrackDeviceAndReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->cookie(ActivityLogger::DEVICE_COOKIE)) {
            $deviceId = (string) Str::uuid();
            $request->cookies->set(ActivityLogger::DEVICE_COOKIE, $deviceId);
            Cookie::queue(ActivityLogger::DEVICE_COOKIE, $deviceId, 60 * 24 * 365 * 5);
        }

        if ($code = $request->query('ref')) {
            Cookie::queue(ReferralService::COOKIE, strtoupper(substr((string) $code, 0, 20)), 60 * 24 * (int) config('cashback.referral_cookie_days'));
        }

        return $next($request);
    }
}
