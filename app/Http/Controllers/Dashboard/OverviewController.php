<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\BonusType;
use App\Http\Controllers\Controller;
use App\Services\NewMemberBonusService;
use App\Services\Telegram\TelegramBot;
use App\Services\UserLevelService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request, UserLevelService $levels, TelegramBot $telegram, NewMemberBonusService $bonuses): View
    {
        $user = $request->user()->load('wallet', 'level');

        $onboardingSteps = $bonuses->onboardingSteps($user);
        $onboardingBonus = (int) setting('onboarding_bonus');
        $allStepsDone = ! collect($onboardingSteps)->contains('done', false);
        // Danh sách việc cần làm: ẩn khi đã nhận thưởng, hoặc đã xong hết mà chương trình thưởng đang tắt
        $showOnboarding = ! $bonuses->hasClaimedOnboarding($user) && ($onboardingBonus > 0 || ! $allStepsDone);

        return view('dashboard.index', [
            'user' => $user,
            'onboarding' => $showOnboarding ? [
                'steps' => $onboardingSteps,
                'completed' => collect($onboardingSteps)->where('done', true)->count(),
                'allDone' => $allStepsDone,
                'bonus' => $onboardingBonus,
                'firstOrderBonus' => $user->bonuses()->where('type', BonusType::FirstOrder)->exists() ? 0 : (int) setting('first_order_bonus'),
                'firstOrderMin' => (int) setting('first_order_bonus_min_order'),
            ] : null,
            // Gợi ý liên kết Telegram: admin bật, bot đã cấu hình, user chưa liên kết và chưa bấm ẩn; danh sách việc cần làm đã có bước này thì không lặp lại
            'showTelegramPrompt' => ! $showOnboarding && setting('telegram_dashboard_prompt') && $telegram->isConfigured() && ! $user->telegram_chat_id && ! $user->telegram_prompt_dismissed_at,
            'wallet' => $user->wallet,
            'recentOrders' => $user->orders()->with('platform')->latest('purchased_at')->limit(5)->get(),
            'approvedOrders' => $levels->approvedOrderCount($user),
            'nextLevel' => $levels->nextLevel($user),
        ]);
    }
}
