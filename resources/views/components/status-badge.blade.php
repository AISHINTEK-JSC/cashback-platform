@props(['status'])

@php
    $classes = match ($status->getColor()) {
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/20',
        'info' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'primary' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        default => 'bg-stone-100 text-stone-700 ring-stone-500/20',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset', $classes]) }}>{{ $status->getLabel() }}</span>
