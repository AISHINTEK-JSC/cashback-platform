<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\CommissionRule;
use App\Models\Platform;
use App\Models\TrackingLink;
use App\Models\User;
use App\Services\Affiliate\AffiliateManager;
use Illuminate\Support\Str;

/**
 * Nhận link sản phẩm người dùng dán vào, trả về link hoàn tiền gắn sub_id riêng.
 */
class TrackingLinkService
{
    public function __construct(private readonly AffiliateManager $affiliates) {}

    public function create(User $user, string $url): TrackingLink
    {
        $url = trim($url);

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new BusinessRuleException('Link không hợp lệ. Hãy dán đúng link sản phẩm cần hoàn tiền.');
        }

        $code = $this->affiliates->detectPlatformCode($url);
        $platform = $code ? Platform::query()->where('code', $code)->first() : null;

        if ($platform?->isComingSoon()) {
            throw new BusinessRuleException("Hoàn tiền {$platform->name} sắp ra mắt, hiện chưa tạo được link.");
        }

        if (! $platform?->is_active) {
            throw new BusinessRuleException('Sàn này hiện chưa được hỗ trợ hoàn tiền.');
        }

        $subId = $this->generateSubId($user);
        $generated = $this->affiliates->driver($platform)->generateLink($platform, $url, $subId);

        return $user->trackingLinks()->create([
            'platform_id' => $platform->id,
            'sub_id' => $subId,
            'original_url' => $url,
            'shop_id' => $generated->shopId,
            'product_id' => $generated->productId,
            'product_name' => $generated->productName,
            'product_image' => $generated->productImage,
            'price' => $generated->price,
            'estimated_rate' => $this->estimatedRate($user, $platform, $generated->commissionRate),
            'affiliate_url' => $generated->affiliateUrl,
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * % hoàn dự kiến = % hoa hồng sản phẩm × % chia cho user × (1 + % thưởng hạng).
     */
    public function estimatedRate(User $user, Platform $platform, ?float $productCommissionRate): ?float
    {
        if ($productCommissionRate === null) {
            return null;
        }

        $rule = CommissionRule::query()
            ->where('platform_id', $platform->id)
            ->whereNull('category_code')
            ->effectiveAt(now())
            ->latest('effective_from')
            ->first();

        if (! $rule) {
            return null;
        }

        $bonus = (float) ($user->level?->bonus_rate ?? 0);

        return round($productCommissionRate * (float) $rule->user_rate / 100 * (1 + $bonus / 100), 2);
    }

    /**
     * Chỉ chữ và số vì Shopee không nhận ký tự đặc biệt trong subIds.
     */
    private function generateSubId(User $user): string
    {
        do {
            $subId = 'u'.base_convert((string) $user->id, 10, 36).Str::lower(Str::random(10));
        } while (TrackingLink::query()->where('sub_id', $subId)->exists());

        return $subId;
    }
}
