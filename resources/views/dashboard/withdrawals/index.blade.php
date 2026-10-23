<x-layouts.dashboard title="Rút tiền" description="Rút số dư khả dụng về tài khoản ngân hàng hoặc ví điện tử của bạn.">
    <div class="grid gap-6 xl:grid-cols-[380px_1fr]">
        <div class="space-y-4">
            <livewire:withdraw-form />
            <a href="{{ route('dashboard.bank-accounts.index') }}" class="flex items-center gap-3 rounded-2xl border border-dashed border-stone-300 p-4 text-sm text-stone-600 transition hover:border-brand-300 hover:bg-white hover:text-stone-900">
                <x-heroicon-o-building-library class="size-5 shrink-0 text-stone-400" />
                <span class="flex-1">Quản lý tài khoản nhận tiền</span>
                <x-heroicon-m-chevron-right class="size-5 text-stone-400" />
            </a>
        </div>

        <div>
            <x-section-header title="Lịch sử rút tiền" />
            @if ($withdrawals->isEmpty())
                <x-empty-state icon="banknotes" title="Chưa có yêu cầu rút tiền">Yêu cầu rút tiền của bạn sẽ hiện ở đây kèm trạng thái xử lý.</x-empty-state>
            @else
                <div class="card overflow-x-auto">
                    <table class="table-base min-w-[560px]">
                        <thead><tr><th>Mã</th><th>Tài khoản</th><th class="text-right">Số tiền</th><th>Trạng thái</th><th><span class="sr-only">Thao tác</span></th></tr></thead>
                        <tbody>
                            @foreach ($withdrawals as $withdrawal)
                                <tr class="hover:bg-stone-50">
                                    <td>
                                        <p class="font-mono font-medium text-stone-900">{{ $withdrawal->code }}</p>
                                        <p class="text-xs text-stone-500">{{ $withdrawal->created_at->format('d/m/Y H:i') }}</p>
                                    </td>
                                    <td>
                                        <p class="text-stone-900">{{ $withdrawal->bank_name }}</p>
                                        <p class="text-xs text-stone-500">{{ $withdrawal->account_number }}</p>
                                    </td>
                                    <td class="text-right tabular-nums">
                                        <p class="font-semibold">{{ vnd($withdrawal->amount) }}</p>
                                        @if ($withdrawal->fee > 0)<p class="text-xs text-stone-500">Phí {{ vnd($withdrawal->fee) }}</p>@endif
                                    </td>
                                    <td>
                                        <x-status-badge :status="$withdrawal->status" />
                                        @if ($withdrawal->reject_reason)<p class="mt-1 max-w-48 text-xs text-red-600">{{ $withdrawal->reject_reason }}</p>@endif
                                    </td>
                                    <td class="text-right">
                                        @if ($withdrawal->status === \App\Enums\WithdrawalStatus::Pending)
                                            <form method="POST" action="{{ route('dashboard.withdrawals.cancel', $withdrawal) }}" onsubmit="return confirm('Huỷ yêu cầu rút tiền này?')">
                                                @csrf
                                                <button type="submit" class="btn btn-ghost !px-2.5 !py-1.5 text-xs text-red-600 hover:!bg-red-50 hover:!text-red-700">Huỷ</button>
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
