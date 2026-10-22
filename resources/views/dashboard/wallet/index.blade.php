<x-layouts.dashboard title="Biến động số dư">
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Chờ duyệt" :value="vnd($wallet->pending_balance)" />
        <x-stat-card label="Có thể rút" :value="vnd($wallet->available_balance)" tone="brand" />
        <x-stat-card label="Đang rút" :value="vnd($wallet->frozen_balance)" />
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <a href="{{ route('dashboard.wallet.index') }}" @class(['btn !py-2', 'btn-primary' => empty($filters['vi']), 'btn-secondary' => ! empty($filters['vi'])])>Tất cả</a>
        @foreach (\App\Enums\WalletBucket::cases() as $bucket)
            <a href="{{ route('dashboard.wallet.index', ['vi' => $bucket->value]) }}" @class(['btn !py-2', 'btn-primary' => ($filters['vi'] ?? null) === $bucket->value, 'btn-secondary' => ($filters['vi'] ?? null) !== $bucket->value])>{{ $bucket->getLabel() }}</a>
        @endforeach
    </div>

    @if ($transactions->isEmpty())
        <x-empty-state class="mt-4" icon="queue-list" title="Chưa có giao dịch" />
    @else
        <div class="card mt-4 overflow-x-auto">
            <table class="table-base min-w-[640px]">
                <thead><tr><th>Thời gian</th><th>Nội dung</th><th>Số dư</th><th class="text-right">Số tiền</th><th class="text-right">Số dư sau</th></tr></thead>
                <tbody>
                    @foreach ($transactions as $tx)
                        <tr>
                            <td class="whitespace-nowrap text-stone-600">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <p class="text-stone-900">{{ $tx->description ?? $tx->type->getLabel() }}</p>
                                <p class="text-xs text-stone-500">{{ $tx->type->getLabel() }}</p>
                            </td>
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
