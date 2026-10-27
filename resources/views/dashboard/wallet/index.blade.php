<x-layouts.dashboard title="Biến động số dư" description="Mọi khoản cộng, trừ trong ví của bạn.">
    <x-slot:actions>
        <a href="{{ route('dashboard.withdrawals.index') }}" class="btn btn-primary"><x-heroicon-o-banknotes class="size-5" /> Rút tiền</a>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Có thể rút" icon="wallet" :value="vnd($wallet->available_balance)" tone="brand" />
        <x-stat-card label="Chờ duyệt" icon="clock" :value="vnd($wallet->pending_balance)" />
        <x-stat-card label="Đang rút" icon="arrow-path" :value="vnd($wallet->frozen_balance)" />
    </div>

    <x-earnings-chart :earnings="$earnings" class="mt-6" />

    <div class="mt-8 mb-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <h2 class="font-semibold text-stone-900">Lịch sử giao dịch</h2>
            <a href="{{ route('dashboard.wallet.export', array_filter(['vi' => $filters['vi'] ?? null])) }}" class="btn btn-ghost !px-2.5 !py-1.5 text-xs" title="Tải lịch sử giao dịch (mở được bằng Excel)">
                <x-heroicon-o-arrow-down-tray class="size-4" /> Xuất CSV
            </a>
        </div>
        <nav class="tabs" aria-label="Lọc theo số dư">
            <a href="{{ route('dashboard.wallet.index') }}" @class(['tab', 'tab-active' => empty($filters['vi'])])>Tất cả</a>
            @foreach (\App\Enums\WalletBucket::cases() as $bucket)
                <a href="{{ route('dashboard.wallet.index', ['vi' => $bucket->value]) }}" @class(['tab', 'tab-active' => ($filters['vi'] ?? null) === $bucket->value])>{{ $bucket->getLabel() }}</a>
            @endforeach
        </nav>
    </div>

    @if ($transactions->isEmpty())
        <x-empty-state icon="queue-list" title="Chưa có giao dịch">Tiền hoàn, thưởng điểm danh và hoa hồng giới thiệu sẽ hiện ở đây.</x-empty-state>
    @else
        <div class="card overflow-x-auto">
            <table class="table-base min-w-[640px]">
                <thead><tr><th>Nội dung</th><th>Thời gian</th><th>Số dư</th><th class="text-right">Số tiền</th><th class="text-right">Số dư sau</th></tr></thead>
                <tbody>
                    @foreach ($transactions as $tx)
                        <tr class="hover:bg-stone-50">
                            <td>
                                <div class="flex items-start gap-3">
                                    <span @class([
                                        'mt-0.5 grid size-8 shrink-0 place-items-center rounded-full',
                                        'bg-emerald-50 text-emerald-600' => $tx->amount > 0,
                                        'bg-red-50 text-red-500' => $tx->amount <= 0,
                                    ])>
                                        @if ($tx->amount > 0)
                                            <x-heroicon-m-arrow-down-left class="size-4" />
                                        @else
                                            <x-heroicon-m-arrow-up-right class="size-4" />
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-stone-900">{{ $tx->description ?? $tx->type->getLabel() }}</p>
                                        <p class="text-xs text-stone-500">{{ $tx->type->getLabel() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap text-stone-600">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                            <td><x-status-badge :status="$tx->bucket" /></td>
                            <td @class(['text-right font-semibold tabular-nums whitespace-nowrap', 'text-emerald-700' => $tx->amount > 0, 'text-red-600' => $tx->amount < 0])>{{ $tx->amount > 0 ? '+' : '' }}{{ vnd($tx->amount) }}</td>
                            <td class="text-right text-stone-600 tabular-nums">{{ vnd($tx->balance_after) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $transactions->links() }}</div>
    @endif
</x-layouts.dashboard>
