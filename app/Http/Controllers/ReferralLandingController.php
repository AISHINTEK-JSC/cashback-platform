<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

class ReferralLandingController extends Controller
{
    public function __invoke(string $code, ReferralService $referrals): RedirectResponse
    {
        if ($referrals->findReferrer($code)) {
            Cookie::queue(ReferralService::COOKIE, strtoupper($code), 60 * 24 * (int) config('cashback.referral_cookie_days'));
        }

        return redirect()->route(auth()->check() ? 'home' : 'register');
    }
}
