<div class="card p-5 sm:p-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-sm text-stone-500">Số dư khả dụng</p>
            <p class="text-2xl font-bold text-stone-900 tabular-nums">{{ vnd($wallet->available_balance) }}</p>
        </div>
        @if ($wallet->frozen_balance > 0)
            <div class="text-right">
                <p class="text-sm text-stone-500">Đang chờ rút</p>
                <p class="font-semibold text-stone-700 tabular-nums">{{ vnd($wallet->frozen_balance) }}</p>
            </div>
        @endif
    </div>

    @if ($bankAccounts->isEmpty())
        <div class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">
            Bạn chưa có tài khoản nhận tiền.
            <a href="{{ route('dashboard.bank-accounts.index') }}" class="font-semibold underline">Thêm tài khoản ngân hàng</a> để rút tiền.
        </div>
    @else
        <form wire:submit="submit" class="mt-5 space-y-4">
            <div>
                <label class="label" for="bankAccountId">Nhận tiền về</label>
                <select id="bankAccountId" wire:model="bankAccountId" class="input">
                    @foreach ($bankAccounts as $account)
                        <option value="{{ $account->id }}" wire:key="bank-{{ $account->id }}">{{ $account->bank_name }} - {{ $account->maskedNumber() }} - {{ $account->account_name }}@if ($availableAt = $account->withdrawableFrom()) (nhận tiền từ {{ $availableAt->format('H:i d/m') }})@endif</option>
                    @endforeach
                </select>
                @error('bankAccountId') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" for="amount">Số tiền muốn rút</label>
                <div class="flex gap-2">
                    <input id="amount" type="number" inputmode="numeric" min="0" step="1000" wire:model.live.debounce.400ms="amount" class="input" placeholder="Tối thiểu {{ vnd($min) }}">
                    <button type="button" wire:click="withdrawAll" class="btn btn-secondary shrink-0">Rút hết</button>
                </div>
                @error('amount') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            @if ($amount)
                <dl class="space-y-1 rounded-xl bg-stone-50 p-4 text-sm">
                    <div class="flex justify-between"><dt class="text-stone-600">Phí rút</dt><dd class="tabular-nums">{{ vnd($fee) }}</dd></div>
                    <div class="flex justify-between font-semibold"><dt>Thực nhận</dt><dd class="tabular-nums text-emerald-700">{{ vnd(max(0, (int) $amount - $fee)) }}</dd></div>
                </dl>
            @endif

            <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">Tạo yêu cầu rút tiền</span>
                <span wire:loading wire:target="submit">Đang xử lý...</span>
            </button>
        </form>
    @endif
</div>
