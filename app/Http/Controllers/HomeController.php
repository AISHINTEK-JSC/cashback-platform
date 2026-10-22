<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\Platform;
use App\Models\Post;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $stats = Cache::remember('home.stats', now()->addMinutes(10), fn () => [
            'members' => User::query()->count(),
            'orders' => Order::query()->where('status', '!=', OrderStatus::Cancelled)->count(),
            'paid_out' => (int) Wallet::query()->sum('total_earned'),
        ]);

        $platforms = Platform::query()->active()->orderBy('id')->get()->each(function (Platform $platform): void {
            $platform->setAttribute('user_rate', CommissionRule::query()
                ->where('platform_id', $platform->id)
                ->whereNull('category_code')
                ->effectiveAt(now())
                ->latest('effective_from')
                ->value('user_rate'));
        });

        return view('home', [
            'stats' => $stats,
            'platforms' => $platforms,
            'vouchers' => Voucher::query()->available()->with('platform')->orderBy('sort_order')->latest()->limit(6)->get(),
            'posts' => Post::query()->published()->with('category')->latest('published_at')->limit(3)->get(),
        ]);
    }
}
