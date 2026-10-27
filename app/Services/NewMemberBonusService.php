<?php

namespace App\Services;

use App\Enums\BonusType;
use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use App\Models\UserBonus;
use App\Models\Wallet;
use App\Notifications\BonusGranted;
use App\Services\Fraud\FraudDetector;
use App\Services\Telegram\TelegramBot;
use Illuminate\Support\Facades\DB;

/**
 * Thưởng một lần cho thành viên mới: đơn đầu tiên được duyệt và hoàn thành danh sách hướng dẫn ở trang tổng quan.
 * Mỗi loại chỉ nhận một lần (unique user_id + type), tiền đi qua WalletService như mọi khoản khác.
 */
class NewMemberBonusService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly FraudDetector $fraud,
        private readonly TelegramBot $telegram,
    ) {}

    /**
     * Gọi khi đơn chuyển sang "Có thể rút" (OrderStatusService). Đơn đầu tiên đạt giá trị tối thiểu thì thưởng.
     */
    public function grantFirstOrderBonus(Order $order): ?UserBonus
    {
        $amount = (int) setting('first_order_bonus');

        if ($amount <= 0 || ! $order->user || $order->order_amount < (int) setting('first_order_bonus_min_order') || $this->fraud->hasConfirmedFlag($order->user)) {
            return null;
        }

        return $this->grant($order->user, BonusType::FirstOrder, $amount, $order);
    }

    /**
     * Đơn đã được thưởng bị huỷ: thu hồi khoản thưởng (số dư có thể âm và trừ vào các khoản sau, như tiền hoàn).
     */
    public function revokeFirstOrderBonus(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $bonus = UserBonus::query()
                ->where('order_id', $order->id)
                ->where('type', BonusType::FirstOrder)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->first();

            if (! $bonus) {
                return;
            }

            $this->wallet->post($bonus->user_id, WalletTransactionType::Reversal, WalletBucket::Available, -$bonus->amount, $bonus, "Thu hồi thưởng đơn đầu tiên - đơn {$order->platform->name} {$order->platform_order_id} bị huỷ", allowNegative: true);
            $bonus->update(['revoked_at' => now()]);
        });
    }

    /**
     * Danh sách việc cần làm của thành viên mới, theo thứ tự hiển thị.
     *
     * @return list<array{key: string, label: string, hint: string, done: bool, url: string, action: string}>
     */
    public function onboardingSteps(User $user): array
    {
        $steps = [
            [
                'key' => 'email',
                'label' => 'Xác thực email',
                'hint' => 'Bảo vệ tài khoản và nhận thông báo quan trọng.',
                'done' => $user->hasVerifiedEmail(),
                'url' => route('verification.notice'),
                'action' => 'Xác thực',
            ],
            [
                'key' => 'bank',
                'label' => 'Thêm tài khoản nhận tiền',
                'hint' => 'Ngân hàng hoặc ví điện tử đứng tên bạn để rút tiền hoàn.',
                'done' => $user->bankAccounts()->exists(),
                'url' => route('dashboard.bank-accounts.index'),
                'action' => 'Thêm ngay',
            ],
            [
                'key' => 'link',
                'label' => 'Tạo link hoàn tiền đầu tiên',
                'hint' => 'Dán link sản phẩm Shopee, TikTok Shop, Lazada ở ô bên dưới.',
                'done' => $user->trackingLinks()->exists(),
                'url' => route('dashboard.links.index'),
                'action' => 'Tạo link',
            ],
        ];

        if ($this->telegram->isConfigured()) {
            $steps[] = [
                'key' => 'telegram',
                'label' => 'Liên kết Telegram',
                'hint' => 'Nhận thông báo đơn hàng, tiền về ví và tạo link ngay trong Telegram.',
                'done' => filled($user->telegram_chat_id),
                'url' => route('dashboard.profile.edit'),
                'action' => 'Liên kết',
            ];
        }

        $steps[] = [
            'key' => 'invite',
            'label' => 'Mời 1 người bạn',
            'hint' => 'Bạn bè đăng ký qua link của bạn và xác thực email.',
            'done' => $user->referrals()->whereNotNull('email_verified_at')->exists(),
            'url' => route('dashboard.referrals.index'),
            'action' => 'Lấy link mời',
        ];

        return $steps;
    }

    public function hasClaimedOnboarding(User $user): bool
    {
        return $user->bonuses()->where('type', BonusType::Onboarding)->exists();
    }

    /**
     * Nhận thưởng khi đã hoàn thành mọi bước; kiểm tra lại ở server, không tin trạng thái trên giao diện.
     *
     * @throws BusinessRuleException
     */
    public function claimOnboardingBonus(User $user): UserBonus
    {
        $amount = (int) setting('onboarding_bonus');

        if ($amount <= 0) {
            throw new BusinessRuleException('Chương trình thưởng hoàn thành hướng dẫn đang tạm dừng.');
        }

        if (collect($this->onboardingSteps($user))->contains('done', false)) {
            throw new BusinessRuleException('Bạn cần hoàn thành tất cả các bước trước khi nhận thưởng.');
        }

        if ($this->fraud->hasConfirmedFlag($user)) {
            throw new BusinessRuleException('Tài khoản đang bị tạm khoá nhận thưởng. Vui lòng liên hệ hỗ trợ.');
        }

        return $this->grant($user, BonusType::Onboarding, $amount)
            ?? throw new BusinessRuleException('Bạn đã nhận thưởng này rồi.');
    }

    /**
     * Khoá dòng ví để hai lần gọi đồng thời của cùng user xếp hàng, rồi mới kiểm tra đã nhận chưa.
     */
    private function grant(User $user, BonusType $type, int $amount, ?Order $order = null): ?UserBonus
    {
        $bonus = DB::transaction(function () use ($user, $type, $amount, $order): ?UserBonus {
            Wallet::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($user->bonuses()->where('type', $type)->exists()) {
                return null;
            }

            $bonus = $user->bonuses()->create(['type' => $type, 'amount' => $amount, 'order_id' => $order?->id]);
            $description = $order
                ? "Thưởng đơn đầu tiên - đơn {$order->platform->name} {$order->platform_order_id}"
                : $type->getLabel();

            $this->wallet->post($user, WalletTransactionType::Bonus, WalletBucket::Available, $amount, $bonus, $description);

            return $bonus;
        });

        $bonus?->user->notify(new BonusGranted($bonus));

        return $bonus;
    }
}
