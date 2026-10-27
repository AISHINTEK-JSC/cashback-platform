<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletController extends Controller
{
    /**
     * Nhóm thu nhập trên biểu đồ, theo thứ tự xếp chồng từ dưới lên (cũng là thứ tự màu).
     *
     * @var array<string, array{label: string, types: list<WalletTransactionType>}>
     */
    private const EARNING_SERIES = [
        'cashback' => ['label' => 'Hoàn tiền', 'types' => [WalletTransactionType::Cashback]],
        'referral' => ['label' => 'Giới thiệu', 'types' => [WalletTransactionType::Referral]],
        'bonus' => ['label' => 'Thưởng & điểm danh', 'types' => [WalletTransactionType::Bonus, WalletTransactionType::Checkin]],
    ];

    public function __invoke(Request $request): View
    {
        $filters = $request->validate(['vi' => ['nullable', Rule::enum(WalletBucket::class)]]);

        return view('dashboard.wallet.index', [
            'wallet' => $request->user()->wallet,
            'earnings' => $this->monthlyEarnings($request->user()),
            'transactions' => $request->user()->walletTransactions()
                ->when($filters['vi'] ?? null, fn ($q, $bucket) => $q->where('bucket', $bucket))
                ->latest('id')
                ->paginate(30)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    /**
     * Xuất toàn bộ lịch sử giao dịch (theo bộ lọc số dư đang chọn) ra CSV mở được bằng Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate(['vi' => ['nullable', Rule::enum(WalletBucket::class)]]);
        $user = $request->user();

        return response()->streamDownload(function () use ($user, $filters): void {
            $out = fopen('php://output', 'w');
            // BOM để Excel nhận đúng tiếng Việt UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Thời gian', 'Loại', 'Số dư', 'Số tiền (VND)', 'Số dư sau (VND)', 'Nội dung']);

            $user->walletTransactions()
                ->when($filters['vi'] ?? null, fn ($q, $bucket) => $q->where('bucket', $bucket))
                ->orderBy('id')
                ->chunkById(500, function ($transactions) use ($out): void {
                    foreach ($transactions as $tx) {
                        /** @var WalletTransaction $tx */
                        fputcsv($out, [
                            $tx->created_at?->format('d/m/Y H:i:s'),
                            $tx->type->getLabel(),
                            $tx->bucket->getLabel(),
                            $tx->amount,
                            $tx->balance_after,
                            $tx->description ?? $tx->type->getLabel(),
                        ]);
                    }
                });

            fclose($out);
        }, 'bien-dong-so-du-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Tiền đã vào số dư có thể rút trong 12 tháng gần nhất, theo tháng và nhóm (chưa trừ các khoản thu hồi).
     *
     * @return array{series: array<string, string>, months: list<array{key: string, label: string, values: array<string, int>, total: int}>, max: int, total: int}
     */
    private function monthlyEarnings(User $user): array
    {
        $start = now()->startOfMonth()->subMonths(11);
        $seriesOf = collect(self::EARNING_SERIES)
            ->flatMap(fn (array $series, string $key) => collect($series['types'])->mapWithKeys(fn (WalletTransactionType $type) => [$type->value => $key]));

        $sums = $user->walletTransactions()
            ->where('bucket', WalletBucket::Available)
            ->whereIn('type', $seriesOf->keys())
            ->where('amount', '>', 0)
            ->where('created_at', '>=', $start)
            ->get(['type', 'amount', 'created_at'])
            ->groupBy(fn (WalletTransaction $tx) => $tx->created_at->format('Y-m').'|'.$seriesOf[$tx->type->value])
            ->map(fn ($group) => (int) $group->sum('amount'));

        $months = collect(range(0, 11))->map(function (int $offset) use ($start, $sums): array {
            /** @var Carbon $month */
            $month = $start->copy()->addMonths($offset);
            $values = collect(self::EARNING_SERIES)->map(fn ($series, string $key) => $sums[$month->format('Y-m').'|'.$key] ?? 0)->all();

            return ['key' => $month->format('Y-m'), 'label' => $month->format('m/Y'), 'values' => $values, 'total' => array_sum($values)];
        })->all();

        return [
            'series' => collect(self::EARNING_SERIES)->map(fn (array $series) => $series['label'])->all(),
            'months' => $months,
            'max' => (int) collect($months)->max('total'),
            'total' => (int) collect($months)->sum('total'),
        ];
    }
}
