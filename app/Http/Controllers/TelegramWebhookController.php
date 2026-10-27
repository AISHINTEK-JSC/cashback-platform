<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBot;
use App\Services\Telegram\TelegramUpdateHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * Webhook Telegram gọi khi có tin nhắn mới tới bot (đăng ký bằng php artisan telegram:setup).
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBot $bot, TelegramUpdateHandler $handler): Response
    {
        abort_unless($bot->isConfigured() && hash_equals($bot->webhookSecret(), (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        try {
            $handler->handle($request->json()->all());
        } catch (Throwable $e) {
            // Luôn trả 2xx, nếu không Telegram gửi lại cùng tin nhắn nhiều lần
            report($e);
        }

        return response()->noContent();
    }
}
