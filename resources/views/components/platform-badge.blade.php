@props(['platform'])

@php
    $classes = match ($platform->code) {
        'shopee' => 'bg-[#ee4d2d] text-white',
        'tiktok' => 'bg-ink text-white',
        'lazada' => 'bg-[#0f136d] text-white',
        'adidas' => 'bg-ink text-white',
        'apple' => 'bg-stone-700 text-white',
        default => 'bg-stone-200 text-stone-800',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold whitespace-nowrap', $classes]) }}>{{ $platform->name }}</span>
