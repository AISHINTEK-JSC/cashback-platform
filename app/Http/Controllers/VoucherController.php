<?php

namespace App\Http\Controllers;

use App\Models\CommissionRule;
use App\Models\Platform;
use App\Models\Post;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        // Link lọc cũ ?san=shopee chuyển hẳn sang trang riêng của sàn để Google chỉ index một URL
        if ($code = $request->query('san')) {
            $platform = Platform::query()->active()->where('code', $code)->first();

            return $platform
                ? redirect()->route('vouchers.platform', $platform, 301)
                : redirect()->route('vouchers.index', status: 301);
        }

        return view('vouchers.index', [
            'platforms' => $this->platforms(),
            'current' => null,
            'vouchers' => $this->vouchers()->paginate(24),
            'maxCashbackRate' => null,
            'related' => collect(),
        ]);
    }

    public function platform(Platform $platform): View
    {
        abort_unless($platform->is_active, 404);

        $userRate = CommissionRule::query()
            ->where('platform_id', $platform->id)
            ->whereNull('category_code')
            ->effectiveAt(now())
            ->latest('effective_from')
            ->value('user_rate');

        return view('vouchers.index', [
            'platforms' => $this->platforms(),
            'current' => $platform,
            'vouchers' => $this->vouchers()->where('platform_id', $platform->id)->paginate(24),
            'maxCashbackRate' => $platform->maxCashbackRate($userRate),
            'related' => Post::query()->published()->with('category')
                ->where('title', 'like', "%{$platform->name}%")
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }

    /**
     * @return Collection<int, Platform>
     */
    private function platforms(): Collection
    {
        return Platform::query()->active()->orderBy('id')->get();
    }

    /**
     * @return Builder<Voucher>
     */
    private function vouchers(): Builder
    {
        return Voucher::query()->available()->with('platform')->orderBy('sort_order')->latest();
    }
}
