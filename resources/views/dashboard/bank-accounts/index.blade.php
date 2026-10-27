<x-layouts.dashboard title="Tài khoản nhận tiền" description="Tiền rút sẽ được chuyển về tài khoản mặc định, bạn có thể đổi khi tạo yêu cầu rút.">
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-3">
            @forelse ($accounts as $account)
                <div @class([
                    'card flex items-center gap-4 p-4',
                    '!border-brand-300 ring-2 ring-brand-500/10' => $account->is_default,
                ])>
                    @if ($logo = $banks[$account->bank_code]['logo'] ?? null)
                        {{-- Logo VietQR có nền trắng nên giữ khung trắng ở cả chế độ tối --}}
                        <span class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-xl bg-white p-1 ring-1 ring-stone-200">
                            <img src="{{ $logo }}" alt="" class="max-h-full object-contain" loading="lazy">
                        </span>
                    @else
                        <span @class([
                            'grid size-11 shrink-0 place-items-center rounded-xl',
                            'bg-brand-50 text-brand-600' => $account->is_default,
                            'bg-stone-100 text-stone-500' => ! $account->is_default,
                        ])><x-heroicon-o-building-library class="size-6" /></span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 font-semibold text-stone-900">
                            {{ $account->bank_name }}
                            @if ($account->is_default)
                                <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700"><x-heroicon-s-check-badge class="size-3.5" /> Mặc định</span>
                            @endif
                        </p>
                        <p class="truncate text-sm text-stone-600"><span class="font-mono">{{ $account->account_number }}</span> · {{ $account->account_name }}</p>
                        @if ($availableAt = $account->withdrawableFrom())
                            <p class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-amber-700"><x-heroicon-o-clock class="size-3.5" /> Nhận được tiền rút từ {{ $availableAt->format('H:i d/m/Y') }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @unless ($account->is_default)
                            <form method="POST" action="{{ route('dashboard.bank-accounts.default', $account) }}">
                                @csrf
                                <button type="submit" class="btn btn-ghost !px-3 !py-1.5 text-xs">Đặt mặc định</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('dashboard.bank-accounts.destroy', $account) }}" onsubmit="return confirm('Xoá tài khoản nhận tiền này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost !px-2.5 text-stone-400 hover:!bg-red-50 hover:!text-red-600" title="Xoá" aria-label="Xoá"><x-heroicon-o-trash class="size-5" /></button>
                        </form>
                    </div>
                </div>
            @empty
                <x-empty-state icon="building-library" title="Chưa có tài khoản nhận tiền">Thêm tài khoản ngân hàng hoặc ví điện tử ở khung bên cạnh để rút tiền.</x-empty-state>
            @endforelse
        </div>

        <form method="POST" action="{{ route('dashboard.bank-accounts.store') }}" class="card h-fit space-y-4 p-5 lg:sticky lg:top-24">
            @csrf
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-heroicon-o-plus class="size-5" /></span>
                <p class="font-semibold text-stone-900">Thêm tài khoản</p>
            </div>
            <div>
                <label for="bank_code" class="label">Ngân hàng / ví</label>
                <select id="bank_code" name="bank_code" class="input" required>
                    <option value="">Chọn ngân hàng</option>
                    @foreach ($banks as $code => $bank)
                        <option value="{{ $code }}" @selected(old('bank_code') === $code)>{{ $bank['short_name'] }} - {{ $bank['name'] }}</option>
                    @endforeach
                </select>
                @error('bank_code') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <x-form.input name="account_number" label="Số tài khoản / số điện thoại ví" inputmode="numeric" required />
            <x-form.input name="account_name" label="Tên chủ tài khoản" hint="Viết đúng như trên thẻ, hệ thống tự chuyển thành chữ in hoa không dấu." required />
            <p class="flex gap-2 rounded-xl bg-amber-50 p-3 text-xs text-amber-900"><x-heroicon-o-shield-check class="size-4 shrink-0" /> Tài khoản phải đứng tên bạn. Một tài khoản ngân hàng dùng cho nhiều tài khoản thành viên sẽ bị xem xét gian lận.@if ($holdHours = (int) setting('withdraw_new_bank_hold_hours')) Tài khoản mới thêm nhận được tiền rút sau {{ $holdHours }} giờ để phòng trường hợp tài khoản thành viên bị chiếm.@endif</p>
            <button type="submit" class="btn btn-primary w-full">Lưu tài khoản</button>
        </form>
    </div>
</x-layouts.dashboard>
