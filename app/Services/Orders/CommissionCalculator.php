<?php

namespace App\Services\Orders;

use App\Enums\RewardType;
use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\User;

/**
 * Chia hoa hồng sàn trả về thành phần của người mua, F1 và F2 theo quy tắc có hiệu lực lúc mua.
 */
class CommissionCalculator
{
    /**
     * Quy tắc theo ngành hàng của item có hoa hồng lớn nhất, không có thì dùng quy tắc mặc định của sàn.
     */
    public function resolveRule(Order $order): ?CommissionRule
    {
        $categoryCode = $order->items->sortByDesc('commission_amount')->first()?->category_code;

        return $this->findRule($order->platform_id, $categoryCode, $order)
            ?? ($categoryCode !== null ? $this->findRule($order->platform_id, null, $order) : null);
    }

    /**
     * @return list<array{user: User, type: RewardType, rate: float, amount: int}>
     */
    public function split(Order $order, User $buyer, CommissionRule $rule): array
    {
        $commission = $order->commission_amount;
        $shares = [];

        $userRate = (float) $rule->user_rate;
        $bonusRate = (float) ($buyer->level?->bonus_rate ?? 0);
        $effectiveUserRate = round($userRate * (1 + $bonusRate / 100), 2);

        $shares[] = [
            'user' => $buyer,
            'type' => RewardType::Self,
            'rate' => $effectiveUserRate,
            'amount' => $this->percentOf($commission, $effectiveUserRate),
        ];

        $f1 = $buyer->referrer;

        if ($f1 && $f1->isActive() && (float) $rule->f1_rate > 0) {
            $shares[] = [
                'user' => $f1,
                'type' => RewardType::F1,
                'rate' => (float) $rule->f1_rate,
                'amount' => $this->percentOf($commission, (float) $rule->f1_rate),
            ];
        }

        $f2 = $f1?->referrer;

        if ($f2 && $f2->isActive() && $f2->isNot($buyer) && (float) $rule->f2_rate > 0) {
            $shares[] = [
                'user' => $f2,
                'type' => RewardType::F2,
                'rate' => (float) $rule->f2_rate,
                'amount' => $this->percentOf($commission, (float) $rule->f2_rate),
            ];
        }

        return array_values(array_filter($shares, fn (array $share) => $share['amount'] > 0));
    }

    /**
     * Làm tròn xuống tới đồng để không bao giờ trả vượt hoa hồng nhận được.
     */
    public function percentOf(int $amount, float $rate): int
    {
        return max(0, (int) floor($amount * $rate / 100));
    }

    private function findRule(int $platformId, ?string $categoryCode, Order $order): ?CommissionRule
    {
        return CommissionRule::query()
            ->where('platform_id', $platformId)
            ->when($categoryCode === null, fn ($q) => $q->whereNull('category_code'), fn ($q) => $q->where('category_code', $categoryCode))
            ->effectiveAt($order->purchased_at)
            ->latest('effective_from')
            ->first();
    }
}
