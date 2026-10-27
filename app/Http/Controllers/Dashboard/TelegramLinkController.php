<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\Telegram\TelegramBot;
use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramLinkController extends Controller
{
    /**
     * Mở bot kèm mã liên kết một lần; bot nhận /start <mã> rồi gắn chat với tài khoản.
     */
    public function store(Request $request, TelegramBot $bot): RedirectResponse
    {
        if (! $bot->isConfigured()) {
            return back()->with('error', 'Tính năng Telegram đang tạm ngưng, vui lòng thử lại sau.');
        }

        return redirect()->away($bot->startUrl(TelegramUpdateHandler::issueLinkToken($request->user())));
    }

    /**
     * Ẩn gợi ý liên kết ở trang tổng quan; mục Telegram trong trang Hồ sơ vẫn còn.
     */
    public function dismissPrompt(Request $request): RedirectResponse
    {
        $request->user()->update(['telegram_prompt_dismissed_at' => now()]);

        return back()->with('success', 'Đã ẩn gợi ý. Bạn vẫn có thể liên kết Telegram trong trang Hồ sơ.');
    }

    public function destroy(Request $request, TelegramBot $bot, ActivityLogger $activity): RedirectResponse
    {
        $user = $request->user();
        $chatId = $user->telegram_chat_id;

        if ($chatId) {
            $user->update(['telegram_chat_id' => null, 'telegram_username' => null, 'telegram_linked_at' => null]);
            $activity->log($user, 'telegram.unlink');

            rescue(fn () => $bot->sendMessage($chatId, 'Tài khoản Ví Hoàn Tiền đã huỷ liên kết với Telegram này từ website.'), report: false);
        }

        return back()->with('success', 'Đã huỷ liên kết Telegram.');
    }
}
