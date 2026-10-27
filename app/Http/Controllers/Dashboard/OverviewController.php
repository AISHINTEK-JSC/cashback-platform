<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramBot;
use App\Services\UserLevelService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request, UserLevelService $levels, TelegramBot $telegram): View
    {
        $user = $request->user()->load('wallet', 'level');

        return view('dashboard.index', [
            'user' => $user,
            // Gợi ý liên kết Telegram: admin bật, bot đã cấu hình, user chưa liên kết và chưa bấm ẩn
            'showTelegramPrompt' => setting('telegram_dashboard_prompt') && $telegram->isConfigured() && ! $user->telegram_chat_id && ! $user->telegram_prompt_dismissed_at,
            'wallet' => $user->wallet,
            'recentOrders' => $user->orders()->with('platform')->latest('purchased_at')->limit(5)->get(),
            'approvedOrders' => $levels->approvedOrderCount($user),
            'nextLevel' => $levels->nextLevel($user),
        ]);
    }
}
