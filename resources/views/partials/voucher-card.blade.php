<div class="card flex flex-col p-5">
    <div class="flex items-start justify-between gap-3">
        <x-platform-badge :platform="$voucher->platform" />
        @if ($voucher->expires_at)
            <span class="text-xs text-stone-500">HSD {{ $voucher->expires_at->format('d/m H:i') }}</span>
        @endif
    </div>
    <p class="mt-3 text-lg font-bold text-brand-700">{{ $voucher->discountLabel() }}</p>
    <p class="mt-1 text-sm text-stone-700">{{ $voucher->title }}</p>
    <p class="mt-1 text-xs text-stone-500">
        Đơn tối thiểu {{ vnd($voucher->min_order) }}@if ($voucher->max_discount), giảm tối đa {{ vnd($voucher->max_discount) }}@endif
    </p>
    <div class="mt-4 flex items-center gap-2 pt-1">
        <span class="flex-1 rounded-lg border border-dashed border-brand-300 bg-brand-50 px-3 py-2 text-center font-mono text-sm font-semibold tracking-wider text-brand-800">{{ $voucher->code }}</span>
        <x-copy-button :text="$voucher->code" label="Lưu mã" />
    </div>
    @if ($voucher->url)
        <a href="{{ $voucher->url }}" target="_blank" rel="noopener nofollow" class="mt-2 text-center text-xs font-medium text-stone-600 hover:text-stone-900">Đến trang lấy mã</a>
    @endif
</div>
