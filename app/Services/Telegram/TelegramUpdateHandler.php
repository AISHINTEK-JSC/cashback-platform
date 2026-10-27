<?php

namespace App\Services\Telegram;

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ReferralService;
use App\Services\TrackingLinkService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Xử lý tin nhắn gửi tới bot: liên kết tài khoản qua /start <mã>, tạo link hoàn tiền khi nhận link sản phẩm và các lệnh tra cứu.
 * Chỉ xử lý chat riêng; nhóm/kênh bị bỏ qua để không lộ số dư.
 */
class TelegramUpdateHandler
{
    public const LINK_TOKEN_TTL_MINUTES = 15;

    private const LINK_TOKEN_CACHE_PREFIX = 'telegram-link:';

    public function __construct(
        private readonly TelegramBot $bot,
        private readonly TrackingLinkService $links,
        private readonly ReferralService $referrals,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * Mã một lần cho nút "Liên kết Telegram" ở trang Hồ sơ, bot nhận lại qua /start <mã>.
     */
    public static function issueLinkToken(User $user): string
    {
        $token = Str::random(40);
        Cache::put(self::LINK_TOKEN_CACHE_PREFIX.$token, $user->id, now()->addMinutes(self::LINK_TOKEN_TTL_MINUTES));

        return $token;
    }

    /**
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update): void
    {
        $message = $update['message'] ?? null;

        if (! is_array($message) || ($message['chat']['type'] ?? null) !== 'private') {
            return;
        }

        $chatId = (int) $message['chat']['id'];
        $username = $message['from']['username'] ?? null;
        $text = trim((string) ($message['text'] ?? ''));
        $user = User::query()->where('telegram_chat_id', $chatId)->first();

        if (str_starts_with($text, '/')) {
            [$command, $argument] = $this->parseCommand($text);

            match ($command) {
                'start' => $this->start($chatId, $username, $user, $argument),
                'taolink' => $argument !== ''
                    ? $this->createLink($chatId, $user, $argument)
                    : $this->bot->sendMessage($chatId, 'Gửi link sản phẩm Shopee, TikTok Shop hoặc Lazada vào đây, mình sẽ trả lại link hoàn tiền.'),
                'sodu' => $this->balance($chatId, $user),
                'donhang' => $this->orders($chatId, $user),
                'ruttien' => $this->withdraw($chatId, $user),
                'gioithieu' => $this->referral($chatId, $user),
                'huylienket' => $this->unlink($chatId, $user),
                default => $this->help($chatId),
            };

            return;
        }

        if ($this->extractUrl($text) !== null) {
            $this->createLink($chatId, $user, $text);

            return;
        }

        $this->bot->sendMessage($chatId, "Mình chưa hiểu tin nhắn này 🙈\nHãy gửi <b>link sản phẩm</b> để lấy link hoàn tiền, hoặc gõ /trogiup để xem hướng dẫn.");
    }

    private function start(int $chatId, ?string $username, ?User $user, string $token): void
    {
        if ($token !== '') {
            $this->link($chatId, $username, $token);

            return;
        }

        if ($user) {
            $this->bot->sendMessage($chatId, 'Chào '.e($user->name)." 👋\nGửi link sản phẩm vào đây để lấy link hoàn tiền, hoặc chọn lệnh trong Menu.");

            return;
        }

        $this->askToLink($chatId, "Chào bạn 👋 Đây là bot của <b>Ví Hoàn Tiền</b>.\n\nĐể dùng bot, hãy liên kết tài khoản: đăng nhập website → <b>Hồ sơ</b> → bấm <b>Liên kết Telegram</b>.");
    }

    private function link(int $chatId, ?string $username, string $token): void
    {
        $userId = Cache::pull(self::LINK_TOKEN_CACHE_PREFIX.$token);
        $user = $userId ? User::query()->find($userId) : null;

        if (! $user) {
            $this->askToLink($chatId, 'Mã liên kết không hợp lệ hoặc đã hết hạn ('.self::LINK_TOKEN_TTL_MINUTES.' phút). Hãy bấm lại <b>Liên kết Telegram</b> trong trang Hồ sơ.');

            return;
        }

        // Một chat Telegram chỉ gắn với một tài khoản: gỡ khỏi tài khoản cũ nếu có
        User::query()->where('telegram_chat_id', $chatId)->whereKeyNot($user->id)->update(['telegram_chat_id' => null, 'telegram_username' => null, 'telegram_linked_at' => null]);
        $user->update(['telegram_chat_id' => $chatId, 'telegram_username' => $username, 'telegram_linked_at' => now()]);
        $this->activity->log($user, 'telegram.link', ['username' => $username]);

        $this->bot->sendMessage(
            $chatId,
            '✅ Đã liên kết với tài khoản <b>'.e($user->name)."</b>.\n\nTừ giờ bạn sẽ nhận thông báo đơn hàng, tiền về ví và rút tiền tại đây. Gửi link sản phẩm bất kỳ để lấy link hoàn tiền.",
            [['Mở Ví Hoàn Tiền', route('dashboard.index')]],
        );
    }

    private function createLink(int $chatId, ?User $user, string $text): void
    {
        if (! $this->ensureCanUse($chatId, $user)) {
            return;
        }

        $url = $this->extractUrl($text);

        if ($url === null) {
            $this->bot->sendMessage($chatId, 'Không tìm thấy link sản phẩm trong tin nhắn. Hãy gửi link dạng https://...');

            return;
        }

        $key = 'link-generate:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 30)) {
            $this->bot->sendMessage($chatId, 'Bạn tạo link quá nhanh, vui lòng thử lại sau ít phút.');

            return;
        }

        RateLimiter::hit($key, 600);

        try {
            $link = $this->links->create($user, $url);
        } catch (BusinessRuleException $e) {
            $this->bot->sendMessage($chatId, '⚠️ '.e($e->getMessage()));

            return;
        }

        $this->activity->log($user, 'link.create', ['tracking_link_id' => $link->id, 'source' => 'telegram']);

        $details = array_filter([
            $link->price ? 'Giá: '.vnd($link->price) : null,
            $link->estimated_rate ? 'Hoàn dự kiến ~'.rtrim(rtrim(number_format((float) $link->estimated_rate, 2, ',', '.'), '0'), ',').'%' : 'Có hoàn tiền',
        ]);

        $this->bot->sendMessage(
            $chatId,
            '✅ Link hoàn tiền '.e($link->platform->name)."\n<b>".e($link->product_name ?? 'Sản phẩm của bạn')."</b>\n".implode(' · ', $details)
                ."\n\n".e($link->redirectUrl())
                ."\n\n<i>Mở link rồi mua ngay trong phiên đó, không bấm link tiếp thị khác trước khi đặt đơn.</i>",
            [['🛒 Mua ngay trên '.$link->platform->name, $link->redirectUrl()]],
        );
    }

    private function balance(int $chatId, ?User $user): void
    {
        if (! $this->ensureLinked($chatId, $user)) {
            return;
        }

        $wallet = $user->wallet;

        $this->bot->sendMessage(
            $chatId,
            "💰 <b>Số dư ví</b>\nCó thể rút: <b>".vnd($wallet->available_balance)."</b>\nChờ sàn duyệt: ".vnd($wallet->pending_balance)
                .($wallet->frozen_balance > 0 ? "\nĐang chờ rút: ".vnd($wallet->frozen_balance) : '')
                ."\nTổng đã nhận: ".vnd($wallet->total_earned),
            [['Xem biến động số dư', route('dashboard.wallet.index')]],
        );
    }

    private function orders(int $chatId, ?User $user): void
    {
        if (! $this->ensureLinked($chatId, $user)) {
            return;
        }

        $orders = $user->orders()->with('platform')->latest('purchased_at')->limit(5)->get();

        if ($orders->isEmpty()) {
            $this->bot->sendMessage($chatId, "Bạn chưa có đơn hàng nào.\nGửi link sản phẩm để lấy link hoàn tiền rồi mua qua link đó nhé.");

            return;
        }

        $lines = $orders->map(fn ($order) => sprintf(
            '• %s %s · %s → hoàn <b>%s</b> · %s',
            e($order->platform->name),
            e($order->platform_order_id),
            vnd($order->order_amount),
            vnd($order->cashback_amount),
            e($order->status->getLabel()),
        ));

        $this->bot->sendMessage($chatId, "🧾 <b>Đơn hàng gần đây</b>\n".$lines->implode("\n"), [['Xem tất cả đơn hàng', route('dashboard.orders.index')]]);
    }

    private function withdraw(int $chatId, ?User $user): void
    {
        if (! $this->ensureLinked($chatId, $user)) {
            return;
        }

        $available = (int) $user->wallet?->available_balance;
        $min = (int) setting('withdraw_min');

        $this->bot->sendMessage(
            $chatId,
            '🏦 Số dư có thể rút: <b>'.vnd($available)."</b>\n"
                .($available >= $min ? 'Bấm nút bên dưới để tạo yêu cầu rút về tài khoản ngân hàng.' : 'Cần tối thiểu '.vnd($min).' để rút tiền.'),
            [['Rút tiền', route('dashboard.withdrawals.index')]],
        );
    }

    private function referral(int $chatId, ?User $user): void
    {
        if (! $this->ensureLinked($chatId, $user)) {
            return;
        }

        $summary = $this->referrals->summary($user);
        $link = route('referral.landing', $user->referral_code);

        $this->bot->sendMessage(
            $chatId,
            "🎁 <b>Giới thiệu bạn bè</b>\nGửi link này cho bạn bè, bạn nhận thêm hoa hồng trên mỗi đơn của họ:\n".e($link)
                ."\n\nĐã mời: {$summary['f1_count']} người (F2: {$summary['f2_count']})\nĐã nhận: ".vnd($summary['f1_earned'] + $summary['f2_earned']),
            [['Chia sẻ link giới thiệu', 'https://t.me/share/url?url='.rawurlencode($link).'&text='.rawurlencode('Mua sắm Shopee, TikTok, Lazada được hoàn tiền, đăng ký qua link của mình nhé!')]],
        );
    }

    private function unlink(int $chatId, ?User $user): void
    {
        if (! $user) {
            $this->bot->sendMessage($chatId, 'Telegram này chưa liên kết với tài khoản nào.');

            return;
        }

        $user->update(['telegram_chat_id' => null, 'telegram_username' => null, 'telegram_linked_at' => null]);
        $this->activity->log($user, 'telegram.unlink');

        $this->bot->sendMessage($chatId, 'Đã huỷ liên kết. Bạn sẽ không nhận thông báo qua Telegram nữa. Có thể liên kết lại bất cứ lúc nào trong trang Hồ sơ.');
    }

    private function help(int $chatId): void
    {
        $this->bot->sendMessage(
            $chatId,
            "📖 <b>Hướng dẫn</b>\n1. Gửi link sản phẩm Shopee, TikTok Shop, Lazada vào đây.\n2. Bấm <b>Mua ngay</b> trong tin trả lời và đặt hàng như bình thường.\n3. Tiền hoàn về ví sau khi sàn duyệt đơn, rút về ngân hàng bất cứ lúc nào.\n\n"
                ."/sodu Số dư ví\n/donhang Đơn hàng gần đây\n/ruttien Rút tiền\n/gioithieu Link giới thiệu bạn bè\n/huylienket Huỷ liên kết",
            [['Câu hỏi thường gặp', route('pages.faq')], ['Gửi yêu cầu hỗ trợ', route('dashboard.tickets.create')]],
        );
    }

    private function ensureLinked(int $chatId, ?User $user): bool
    {
        if (! $user) {
            $this->askToLink($chatId, 'Bạn cần liên kết tài khoản Ví Hoàn Tiền trước: đăng nhập website → <b>Hồ sơ</b> → bấm <b>Liên kết Telegram</b>.');

            return false;
        }

        return true;
    }

    private function ensureCanUse(int $chatId, ?User $user): bool
    {
        if (! $this->ensureLinked($chatId, $user)) {
            return false;
        }

        if (! $user->isActive()) {
            $this->bot->sendMessage($chatId, 'Tài khoản của bạn đang bị khoá. Vui lòng liên hệ hỗ trợ.');

            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->bot->sendMessage($chatId, 'Hãy xác thực email trước khi tạo link hoàn tiền.', [['Xác thực email', route('verification.notice')]]);

            return false;
        }

        return true;
    }

    private function askToLink(int $chatId, string $html): void
    {
        $this->bot->sendMessage($chatId, $html, [['Liên kết tài khoản', route('dashboard.profile.edit')]]);
    }

    /**
     * "/taolink@vihoantien_bot https://..." => ['taolink', 'https://...'].
     *
     * @return array{0: string, 1: string}
     */
    private function parseCommand(string $text): array
    {
        [$command, $argument] = array_pad(preg_split('/\s+/', $text, 2), 2, '');

        return [Str::lower(Str::before(ltrim($command, '/'), '@')), trim($argument)];
    }

    private function extractUrl(string $text): ?string
    {
        return preg_match('~https?://[^\s<>"\']+~iu', $text, $matches) ? rtrim($matches[0], '.,;:!?)]}»…') : null;
    }
}
