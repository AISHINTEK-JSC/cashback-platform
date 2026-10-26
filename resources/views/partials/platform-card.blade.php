@php
    $logoUrl = $platform->logoUrl();
    $maxRate = $comingSoon ? null : $platform->maxCashbackRate($platform->user_rate);
@endphp

<li @class(['card flex flex-col items-center gap-3 p-5 text-center', 'opacity-60' => $comingSoon])>
    <span class="grid h-16 w-28 place-items-center overflow-hidden rounded-2xl bg-white px-2 ring-1 ring-stone-200">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $platform->name }}" class="max-h-12 w-full object-contain" loading="lazy">
        @else
            <x-platform-badge :platform="$platform" />
        @endif
    </span>

    <span class="font-semibold text-stone-900">{{ $platform->name }}</span>

    @if ($comingSoon)
        <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-600">Sắp ra mắt</span>
    @elseif ($maxRate)
        <span class="text-sm text-stone-600">Hoàn đến <strong class="text-base text-brand-600">{{ rtrim(rtrim(number_format($maxRate, 2, ',', '.'), '0'), ',') }}%</strong></span>
    @else
        <span class="text-sm text-stone-600">Đang hoàn tiền</span>
    @endif
</li>
