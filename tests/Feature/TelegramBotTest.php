<?php

use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\TicketReplied;
use App\Services\Telegram\TelegramBot;
use App\Services\Telegram\TelegramUpdateHandler;
use App\Services\WalletService;
use App\Support\Settings;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->withoutVite();
    config(['services.telegram.bot_token' => 'test-token', 'services.telegram.bot_username' => 'vihoantien_bot']);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);

    $this->platform = shopeeWithDefaultRule();
    $this->user = User::factory()->create(['name' => 'Nguyễn Văn A']);
});

function telegramUpdate(string $text, int $chatId = 555, string $chatType = 'private'): array
{
    return ['update_id' => 1, 'message' => [
        'message_id' => 1,
        'from' => ['id' => $chatId, 'username' => 'nguyenvana'],
        'chat' => ['id' => $chatId, 'type' => $chatType],
        'text' => $text,
    ]];
}

function sendToWebhook(array $update): TestResponse
{
    return test()->postJson(route('telegram.webhook'), $update, ['X-Telegram-Bot-Api-Secret-Token' => app(TelegramBot::class)->webhookSecret()]);
}

function lastTelegramText(): string
{
    return (string) collect(Http::recorded())->last()[0]['text'];
}

it('rejects webhook calls without the secret header', function () {
    $this->postJson(route('telegram.webhook'), telegramUpdate('/start'))->assertForbidden();
    $this->postJson(route('telegram.webhook'), telegramUpdate('/start'), ['X-Telegram-Bot-Api-Secret-Token' => 'sai'])->assertForbidden();

    Http::assertNothingSent();
});

it('links the account from the one-time token on the profile page', function () {
    $response = $this->actingAs($this->user)->post(route('dashboard.telegram.link'));
    $token = str($response->headers->get('Location'))->after('https://t.me/vihoantien_bot?start=')->toString();
    expect($token)->toHaveLength(40);

    sendToWebhook(telegramUpdate('/start '.$token))->assertNoContent();

    expect($this->user->fresh())
        ->telegram_chat_id->toBe(555)
        ->telegram_username->toBe('nguyenvana');
    expect(lastTelegramText())->toContain('Đã liên kết với tài khoản <b>Nguyễn Văn A</b>');

    sendToWebhook(telegramUpdate('/start '.$token, chatId: 999));
    expect(lastTelegramText())->toContain('Mã liên kết không hợp lệ hoặc đã hết hạn');
});

it('moves the telegram chat to the newly linked account', function () {
    $other = User::factory()->create(['telegram_chat_id' => 555]);

    sendToWebhook(telegramUpdate('/start '.TelegramUpdateHandler::issueLinkToken($this->user)));

    expect($other->fresh()->telegram_chat_id)->toBeNull()
        ->and($this->user->fresh()->telegram_chat_id)->toBe(555);
});

it('creates a cashback link when a linked member sends a product link', function () {
    $this->user->update(['telegram_chat_id' => 555]);

    sendToWebhook(telegramUpdate('Xem sản phẩm này nhé https://shopee.vn/ao-thun-i.123.456'));

    $link = $this->user->trackingLinks()->sole();
    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/sendMessage')
        && str_contains($request['text'], $link->redirectUrl())
        && $request['reply_markup']['inline_keyboard'][0][0]['url'] === $link->redirectUrl());
});

it('asks unlinked chats to link before creating links', function () {
    sendToWebhook(telegramUpdate('https://shopee.vn/ao-thun-i.123.456'));

    expect(lastTelegramText())->toContain('liên kết tài khoản')
        ->and($this->user->trackingLinks()->count())->toBe(0);
});

it('requires a verified email to create links from the bot', function () {
    $unverified = User::factory()->unverified()->create(['telegram_chat_id' => 777]);

    sendToWebhook(telegramUpdate('/taolink https://shopee.vn/ao-thun-i.123.456', chatId: 777));

    expect(lastTelegramText())->toContain('xác thực email')
        ->and($unverified->trackingLinks()->count())->toBe(0);
});

it('shows the wallet balance', function () {
    $this->user->update(['telegram_chat_id' => 555]);
    app(WalletService::class)->post($this->user, WalletTransactionType::Bonus, WalletBucket::Available, 125000, description: 'Thưởng');

    sendToWebhook(telegramUpdate('/sodu'));

    expect(lastTelegramText())->toContain('Có thể rút: <b>'.vnd(125000).'</b>');
});

it('handles commands addressed to the bot by username', function () {
    $this->user->update(['telegram_chat_id' => 555]);

    sendToWebhook(telegramUpdate('/gioithieu@vihoantien_bot'));

    expect(lastTelegramText())->toContain(route('referral.landing', $this->user->referral_code));
});

it('unlinks from the bot and from the profile page', function () {
    $this->user->update(['telegram_chat_id' => 555]);
    sendToWebhook(telegramUpdate('/huylienket'));
    expect($this->user->fresh()->telegram_chat_id)->toBeNull();

    $this->user->update(['telegram_chat_id' => 555]);
    $this->actingAs($this->user)->delete(route('dashboard.telegram.unlink'))->assertSessionHas('success');
    expect($this->user->fresh()->telegram_chat_id)->toBeNull();
});

it('ignores group chats', function () {
    $this->user->update(['telegram_chat_id' => -100123]);

    sendToWebhook(telegramUpdate('/sodu', chatId: -100123, chatType: 'supergroup'))->assertNoContent();

    Http::assertNothingSent();
});

it('sends notifications to linked members and unlinks when the bot is blocked', function () {
    $ticket = $this->user->supportTickets()->create(['type' => 'other', 'subject' => 'Thiếu đơn', 'status' => 'open']);

    expect((new TicketReplied($ticket))->via($this->user))->toBe(['database']);

    $this->user->update(['telegram_chat_id' => 555]);
    expect((new TicketReplied($ticket))->via($this->user))->toBe(['database', TelegramChannel::class]);

    $this->user->notify(new TicketReplied($ticket));
    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/sendMessage') && $request['chat_id'] === 555);

    $blockedBot = Mockery::mock(TelegramBot::class);
    $blockedBot->shouldReceive('isConfigured')->andReturnTrue();
    $blockedBot->shouldReceive('sendMessage')->andThrow(new RequestException(new Response(new Psr7Response(403, [], '{"ok":false,"description":"Forbidden: bot was blocked by the user"}'))));
    (new TelegramChannel($blockedBot))->send($this->user, new TicketReplied($ticket));

    expect($this->user->fresh()->telegram_chat_id)->toBeNull();
});

it('shows the telegram card on the profile page', function () {
    $this->actingAs($this->user)->get(route('dashboard.profile.edit'))->assertSee('Liên kết Telegram');

    $this->user->update(['telegram_chat_id' => 555, 'telegram_username' => 'nguyenvana', 'telegram_linked_at' => now()]);
    $this->actingAs($this->user)->get(route('dashboard.profile.edit'))->assertSee('@nguyenvana')->assertSee('Huỷ liên kết');
});

it('refuses to register a webhook that is not public https', function () {
    $this->artisan('telegram:setup')->assertFailed();

    Http::assertNothingSent();
});

it('limits how many messages a chat can send per minute', function () {
    app(Settings::class)->set('telegram_chat_rate_limit', 2);
    $this->user->update(['telegram_chat_id' => 555]);

    foreach (range(1, 5) as $attempt) {
        sendToWebhook(telegramUpdate('/sodu'))->assertNoContent();
    }

    $texts = collect(Http::recorded())->map(fn (array $pair) => (string) $pair[0]['text']);
    expect($texts->filter(fn (string $text) => str_contains($text, 'Số dư ví')))->toHaveCount(2)
        ->and($texts->filter(fn (string $text) => str_contains($text, 'gửi tin quá nhanh')))->toHaveCount(1);
});

it('does not limit chats when the admin sets the limit to 0', function () {
    app(Settings::class)->set('telegram_chat_rate_limit', 0);
    $this->user->update(['telegram_chat_id' => 555]);

    foreach (range(1, 25) as $attempt) {
        sendToWebhook(telegramUpdate('/sodu'));
    }

    Http::assertSentCount(25);
});

it('sets the bot display name, commands and webhook on setup', function () {
    config(['app.url' => 'https://vihoantien.id.vn']);
    URL::forceRootUrl('https://vihoantien.id.vn');
    URL::forceScheme('https');

    $this->artisan('telegram:setup')->assertSuccessful();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/setWebhook') && $request['url'] === 'https://vihoantien.id.vn/telegram/webhook');
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/setMyName') && $request['name'] === 'Ví Hoàn Tiền');
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/setMyCommands') && count($request['commands']) === count(TelegramBot::COMMANDS));
});

it('suggests linking telegram on the overview until the member links or hides it', function () {
    $this->actingAs($this->user)->get(route('dashboard.index'))->assertSee('Nhận thông báo và tạo link ngay trên Telegram');

    $this->post(route('dashboard.telegram.dismiss-prompt'))->assertSessionHas('success');
    expect($this->user->fresh()->telegram_prompt_dismissed_at)->not->toBeNull();

    $this->get(route('dashboard.index'))->assertDontSee('Nhận thông báo và tạo link ngay trên Telegram');
    $this->get(route('dashboard.profile.edit'))->assertSee('Liên kết Telegram');
});

it('hides the overview suggestion for linked members, when turned off or when the bot is not set up', function () {
    $linked = User::factory()->create(['telegram_chat_id' => 777]);
    $this->actingAs($linked)->get(route('dashboard.index'))->assertDontSee('Nhận thông báo và tạo link ngay trên Telegram');

    app(Settings::class)->set('telegram_dashboard_prompt', false);
    $this->actingAs($this->user)->get(route('dashboard.index'))->assertDontSee('Nhận thông báo và tạo link ngay trên Telegram');

    app(Settings::class)->set('telegram_dashboard_prompt', true);
    config(['services.telegram.bot_token' => null]);
    $this->actingAs($this->user)->get(route('dashboard.index'))->assertDontSee('Nhận thông báo và tạo link ngay trên Telegram');
});

it('shows the telegram link status in the admin member page', function () {
    $this->user->update(['telegram_chat_id' => 555, 'telegram_username' => 'nguyenvana', 'telegram_linked_at' => now()]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/super/admin/users/'.$this->user->id)
        ->assertSuccessful()
        ->assertSee('Đã liên kết')
        ->assertSee('@nguyenvana')
        ->assertSee('https://t.me/nguyenvana', false);
});

it('mentions telegram data in the privacy policy', function () {
    $this->get(route('pages.privacy'))
        ->assertOk()
        ->assertSee('Khi liên kết Telegram (không bắt buộc)')
        ->assertSee('/huylienket');
});
