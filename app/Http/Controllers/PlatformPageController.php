<?php

namespace App\Http\Controllers;

use App\Models\CommissionRule;
use App\Models\Platform;
use App\Models\Post;
use App\Models\Voucher;
use Illuminate\View\View;

/**
 * Trang "Hoàn tiền <sàn>" cho từng sàn/thương hiệu: cửa vào từ Google cho người tìm "hoàn tiền shopee"...
 */
class PlatformPageController extends Controller
{
    public function __invoke(Platform $platform): View
    {
        abort_unless($platform->is_active || $platform->isComingSoon(), 404);

        $rules = CommissionRule::query()
            ->where('platform_id', $platform->id)
            ->effectiveAt(now())
            ->orderByRaw('category_code IS NOT NULL')
            ->orderBy('category_code')
            ->get();

        $defaultRate = $rules->firstWhere('category_code', null)?->user_rate;

        return view('platforms.show', [
            'platform' => $platform,
            'comingSoon' => ! $platform->is_active,
            'rules' => $rules,
            'userRate' => $defaultRate,
            'maxCashbackRate' => $platform->maxCashbackRate($defaultRate),
            'vouchers' => $platform->is_active
                ? Voucher::query()->available()->with('platform')->where('platform_id', $platform->id)->orderBy('sort_order')->latest()->limit(6)->get()
                : collect(),
            'voucherCount' => $platform->is_active ? Voucher::query()->available()->where('platform_id', $platform->id)->count() : 0,
            'posts' => Post::query()->published()->with('category')->where('title', 'like', "%{$platform->name}%")->latest('published_at')->limit(3)->get(),
            'otherPlatforms' => Platform::query()->active()->whereKeyNot($platform->id)->orderBy('id')->get(),
        ]);
    }
}
