<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Trang "Minh bạch chi trả": số liệu tổng hợp công khai, không lộ thông tin cá nhân (tên che, không có số tài khoản).
 */
class TransparencyController extends Controller
{
    public const CACHE_KEY = 'transparency.stats';

    public function __invoke(): View
    {
        abort_unless(setting('transparency_page'), 404);

        return view('pages.transparency', ['stats' => Cache::remember(self::CACHE_KEY, now()->addHour(), fn () => $this->stats())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(): array
    {
        $paid = fn () => Withdrawal::query()->where('status', WithdrawalStatus::Paid);

        // Thời gian xử lý tính bằng trung vị 200 yêu cầu gần nhất, ít bị lệch bởi vài yêu cầu chờ xác minh lâu
        $minutes = $paid()->whereNotNull('paid_at')->latest('paid_at')->limit(200)->get(['created_at', 'paid_at'])
            ->map(fn (Withdrawal $withdrawal) => $withdrawal->created_at->diffInMinutes($withdrawal->paid_at))
            ->sort()
            ->values();

        $start = now()->startOfMonth()->subMonths(5);
        $monthlySums = $paid()->where('paid_at', '>=', $start)->get(['net_amount', 'paid_at'])
            ->groupBy(fn (Withdrawal $withdrawal) => $withdrawal->paid_at->format('Y-m'))
            ->map(fn ($group) => (int) $group->sum('net_amount'));

        return [
            'total_paid' => (int) $paid()->sum('net_amount'),
            'paid_count' => $paid()->count(),
            'paid_members' => $paid()->distinct()->count('user_id'),
            'median_minutes' => $minutes->isEmpty() ? null : (int) $minutes[intdiv($minutes->count(), 2)],
            'total_credited' => (int) Wallet::query()->sum('total_earned'),
            'orders' => Order::query()->where('status', '!=', OrderStatus::Cancelled)->count(),
            'members' => User::query()->count(),
            'monthly' => collect(range(0, 5))->map(function (int $offset) use ($start, $monthlySums): array {
                $month = $start->copy()->addMonths($offset);

                return ['label' => $month->format('m/Y'), 'amount' => $monthlySums[$month->format('Y-m')] ?? 0];
            })->all(),
            'recent' => $paid()->with('user:id,name')->latest('paid_at')->limit(10)->get()
                ->map(fn (Withdrawal $withdrawal) => [
                    'member' => static::maskName($withdrawal->user?->name),
                    'bank' => $withdrawal->destinationName(),
                    'amount' => (int) $withdrawal->net_amount,
                    'paid_at' => $withdrawal->paid_at?->toIso8601String(),
                    'minutes' => $withdrawal->paid_at ? (int) $withdrawal->created_at->diffInMinutes($withdrawal->paid_at) : null,
                ])->all(),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * "Lê Văn Thành" => "L***h": đủ để thấy là người thật, không đủ để nhận ra ai.
     */
    public static function maskName(?string $name): string
    {
        $name = trim((string) $name);

        if (mb_strlen($name) < 2) {
            return 'Thành viên';
        }

        return Str::upper(mb_substr($name, 0, 1)).'***'.mb_substr($name, -1);
    }
}
