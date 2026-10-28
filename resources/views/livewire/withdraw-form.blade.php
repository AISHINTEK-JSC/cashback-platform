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

    @if (count($methods) > 1)
        <div class="tabs mt-5 flex w-full" role="group" aria-label="Cách nhận tiền">
            @foreach ($methods as $option)
                <button
                    type="button"
                    wire:key="method-{{ $option->value }}"
                    wire:click="$set('method', '{{ $option->value }}')"
                    aria-pressed="{{ $selectedMethod === $option ? 'true' : 'false' }}"
                    @class(['tab flex-1 text-center leading-tight whitespace-normal', 'tab-active' => $selectedMethod === $option])
                >{{ $option->shortLabel() }}</button>
            @endforeach
        </div>
        @error('method') <p class="field-error">{{ $message }}</p> @enderror
    @endif

    @if ($selectedMethod === \App\Enums\WithdrawalMethod::Bank)
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
    @else
        <form wire:submit="submit" class="mt-5 space-y-4" wire:key="phone-form-{{ $selectedMethod->value }}">
            <fieldset>
                <legend class="label">Nhà mạng</legend>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($carriers as $carrierOption)
                        <label wire:key="carrier-{{ $carrierOption->value }}" class="flex cursor-pointer items-center justify-center rounded-xl border border-stone-300 bg-surface px-3 py-2 text-sm font-medium text-stone-700 transition hover:border-stone-400 has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:text-brand-700 has-focus-visible:ring-2 has-focus-visible:ring-brand-500/40">
                            <input type="radio" wire:model="carrier" value="{{ $carrierOption->value }}" class="sr-only">
                            {{ $carrierOption->getLabel() }}
                        </label>
                    @endforeach
                </div>
                @error('carrier') <p class="field-error">{{ $message }}</p> @enderror
            </fieldset>

            @if ($selectedMethod === \App\Enums\WithdrawalMethod::PhoneTopup)
                <div>
                    <label class="label" for="phoneNumber">Số điện thoại cần nạp</label>
                    <input id="phoneNumber" type="tel" inputmode="tel" autocomplete="tel" wire:model="phoneNumber" class="input" placeholder="VD: 0981234567">
                    @error('phoneNumber') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endif

            <fieldset>
                <legend class="label">Mệnh giá</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($phoneAmounts as $phoneAmount)
                        <label wire:key="amount-{{ $phoneAmount }}" class="flex cursor-pointer items-center justify-center rounded-xl border border-stone-300 bg-surface px-2 py-2 text-sm font-medium text-stone-700 tabular-nums transition hover:border-stone-400 has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:text-brand-700 has-disabled:cursor-not-allowed has-disabled:opacity-40 has-focus-visible:ring-2 has-focus-visible:ring-brand-500/40">
                            <input type="radio" wire:model="amount" value="{{ $phoneAmount }}" class="sr-only" @disabled($phoneAmount > $wallet->available_balance)>
                            {{ vnd($phoneAmount) }}
                        </label>
                    @endforeach
                </div>
                @error('amount') <p class="field-error">{{ $message }}</p> @enderror
            </fieldset>

            <p class="rounded-xl bg-stone-50 p-4 text-sm text-stone-600">
                Không mất phí, bạn nhận đúng mệnh giá đã chọn.
                @if ($selectedMethod === \App\Enums\WithdrawalMethod::PhoneCard)
                    Serial và mã thẻ sẽ hiện trong lịch sử rút tiền khi yêu cầu được xử lý.
                @else
                    Hãy kiểm tra đúng nhà mạng của số được nạp.
                @endif
            </p>

            <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">{{ $selectedMethod === \App\Enums\WithdrawalMethod::PhoneCard ? 'Tạo yêu cầu đổi thẻ cào' : 'Tạo yêu cầu nạp tiền' }}</span>
                <span wire:loading wire:target="submit">Đang xử lý...</span>
            </button>
        </form>
    @endif
</div>
