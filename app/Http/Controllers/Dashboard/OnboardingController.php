<?php

namespace App\Http\Controllers\Dashboard;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\NewMemberBonusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Nút "Nhận thưởng" của danh sách việc cần làm ở trang tổng quan.
 */
class OnboardingController extends Controller
{
    public function __invoke(Request $request, NewMemberBonusService $bonuses, ActivityLogger $activity): RedirectResponse
    {
        try {
            $bonus = $bonuses->claimOnboardingBonus($request->user());
        } catch (BusinessRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        $activity->log($request->user(), 'bonus.onboarding', ['amount' => $bonus->amount]);

        return back()->with('success', 'Chúc mừng! '.vnd($bonus->amount).' đã được cộng vào số dư có thể rút.');
    }
}
