<x-layouts.dashboard title="Rút tiền">
    <div class="grid gap-6 xl:grid-cols-[380px_1fr]">
        <livewire:withdraw-form />

        <div>
            <h2 class="mb-3 font-semibold text-stone-900">Lịch sử rút tiền</h2>
            @if ($withdrawals->isEmpty())
                <x-empty-state icon="banknotes" title="Chưa có yêu cầu rút tiền" />
            @else
                <div class="card overflow-x-auto">
                    <table class="table-base min-w-[560px]">
                        <thead><tr><th>Mã</th><th>Tài khoản</th><th class="text-right">Số tiền</th><th>Trạng thái</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($withdrawals as $withdrawal)
                                <tr>
                                    <td>
                                        <p class="font-medium text-stone-900">{{ $withdrawal->code }}</p>
                                        <p class="text-xs text-stone-500">{{ $withdrawal->created_at->format('d/m/Y H:i') }}</p>
                                    </td>
                                    <td class="text-stone-600">{{ $withdrawal->bank_name }}<br><span class="text-xs">{{ $withdrawal->account_number }}</span></td>
                                    <td class="text-right tabular-nums">
                                        <p class="font-semibold">{{ vnd($withdrawal->amount) }}</p>
                                        @if ($withdrawal->fee > 0)<p class="text-xs text-stone-500">Phí {{ vnd($withdrawal->fee) }}</p>@endif
                                    </td>
                                    <td>
                                        <x-status-badge :status="$withdrawal->status" />
                                        @if ($withdrawal->reject_reason)<p class="mt-1 text-xs text-red-600">{{ $withdrawal->reject_reason }}</p>@endif
                                    </td>
                                    <td class="text-right">
                                        @if ($withdrawal->status === \App\Enums\WithdrawalStatus::Pending)
                                            <form method="POST" action="{{ route('dashboard.withdrawals.cancel', $withdrawal) }}" onsubmit="return confirm('Huỷ yêu cầu rút tiền này?')">
                                                @csrf
                                                <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Huỷ</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $withdrawals->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.dashboard>
