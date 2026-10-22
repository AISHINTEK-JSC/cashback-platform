<?php

namespace App\Services\Orders;

use App\Enums\RewardStatus;
use App\Enums\RewardType;
use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Models\Order;
use App\Models\OrderReward;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;

/**
 * Vòng đời tiền thưởng của một đơn: tạo (chờ duyệt) → chuyển khả dụng, hoặc thu hồi khi huỷ.
 */
class OrderRewardService
{
    public function __construct(
        private readonly CommissionCalculator $calculator,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Tạo phần thưởng cho người mua và F1/F2, ghi vào số dư chờ duyệt. Bỏ qua nếu đơn đã có thưởng.
     */
    public function createRewards(Order $order): bool
    {
        if (! $order->user_id || $order->rewards()->exists()) {
            return false;
        }

        $order->loadMissing(['user.level', 'user.referrer.referrer', 'items']);
        $rule = $this->calculator->resolveRule($order);

        if (! $rule) {
            report(new \RuntimeException("Không tìm thấy quy tắc hoa hồng cho đơn #{$order->id} (platform {$order->platform_id})."));

            return false;
        }

        DB::transaction(function () use ($order, $rule): void {
            $shares = $this->calculator->split($order, $order->user, $rule);

            foreach ($shares as $share) {
                $reward = $order->rewards()->create([
                    'user_id' => $share['user']->id,
                    'type' => $share['type'],
                    'rate' => $share['rate'],
                    'amount' => $share['amount'],
                    'status' => RewardStatus::Pending,
                ]);

                $this->wallet->post(
                    $reward->user_id,
                    $this->ledgerType($reward),
                    WalletBucket::Pending,
                    $reward->amount,
                    $reward,
                    $this->describe($reward, 'Ghi nhận'),
                );
            }

            $order->forceFill([
                'commission_rule_id' => $rule->id,
                'cashback_amount' => collect($shares)->firstWhere('type', RewardType::Self)['amount'] ?? 0,
            ])->save();
        });

        return true;
    }

    /**
     * Sàn đổi hoa hồng khi đơn còn chờ (hoàn 1 phần...). Cập nhật số tiền hiển thị theo tỷ lệ đã chốt;
     * sổ cái chờ duyệt giữ nguyên, phần chênh được xử lý khi release().
     */
    public function recalculate(Order $order): void
    {
        $order->rewards()->where('status', RewardStatus::Pending)->get()->each(function (OrderReward $reward) use ($order): void {
            $reward->update(['amount' => $this->calculator->percentOf($order->commission_amount, (float) $reward->rate)]);
        });

        $order->forceFill([
            'cashback_amount' => (int) $order->rewards()->where('type', RewardType::Self)->value('amount'),
        ])->save();
    }

    /**
     * Đơn được duyệt: rút khoản đã ghi khỏi "chờ duyệt", cộng số tiền cuối cùng vào "khả dụng".
     */
    public function release(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order->rewards()->where('status', RewardStatus::Pending)->lockForUpdate()->get()->each(function (OrderReward $reward): void {
                $type = $this->ledgerType($reward);
                $postedPending = $this->wallet->postedAmount($reward, $type, WalletBucket::Pending);

                if ($postedPending !== 0) {
                    $this->wallet->post($reward->user_id, WalletTransactionType::Release, WalletBucket::Pending, -$postedPending, $reward, $this->describe($reward, 'Duyệt'), allowNegative: true);
                }

                if ($reward->amount > 0) {
                    $this->wallet->post($reward->user_id, $type, WalletBucket::Available, $reward->amount, $reward, $this->describe($reward, 'Có thể rút'));
                }

                $reward->update(['status' => RewardStatus::Available]);
            });
        });
    }

    /**
     * Đơn bị huỷ: thu hồi tiền ở bucket hiện tại của từng phần thưởng.
     * Nếu tiền đã khả dụng và user đã rút, số dư có thể âm và được trừ vào các đơn sau.
     */
    public function cancel(Order $order, string $reason = 'Đơn bị huỷ'): void
    {
        DB::transaction(function () use ($order, $reason): void {
            $order->rewards()->where('status', '!=', RewardStatus::Cancelled)->lockForUpdate()->get()->each(function (OrderReward $reward) use ($reason): void {
                $type = $this->ledgerType($reward);
                $bucket = $reward->status === RewardStatus::Available ? WalletBucket::Available : WalletBucket::Pending;
                $posted = $this->wallet->postedAmount($reward, $type, $bucket);

                if ($posted !== 0) {
                    $this->wallet->post($reward->user_id, WalletTransactionType::Reversal, $bucket, -$posted, $reward, $this->describe($reward, $reason), allowNegative: true);
                }

                $reward->update(['status' => RewardStatus::Cancelled]);
            });
        });
    }

    private function ledgerType(OrderReward $reward): WalletTransactionType
    {
        return $reward->type === RewardType::Self ? WalletTransactionType::Cashback : WalletTransactionType::Referral;
    }

    private function describe(OrderReward $reward, string $action): string
    {
        $order = $reward->order;

        return sprintf('%s %s - đơn %s %s', $action, mb_strtolower($reward->type->getLabel()), $order->platform->name, $order->platform_order_id);
    }
}
