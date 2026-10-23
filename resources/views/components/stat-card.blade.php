@props(['label', 'value', 'hint' => null, 'tone' => 'default', 'icon' => null])

<div {{ $attributes->class([
    'relative overflow-hidden rounded-2xl border p-5',
    'border-brand-600 bg-linear-to-br from-brand-500 to-brand-700 dark:to-brand-600 text-white shadow-sm shadow-brand-600/20' => $tone === 'brand',
    'border-stone-200 bg-surface shadow-sm' => $tone !== 'brand',
]) }}>
    <div class="flex items-start justify-between gap-3">
        <p @class(['text-sm font-medium', 'text-white/80' => $tone === 'brand', 'text-stone-500' => $tone !== 'brand'])>{{ $label }}</p>
        @if ($icon)
            <span @class([
                'grid size-9 shrink-0 place-items-center rounded-xl',
                'bg-white/15 text-white' => $tone === 'brand',
                'bg-stone-100 text-stone-500' => $tone !== 'brand',
            ])>
                <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" />
            </span>
        @endif
    </div>
    <p @class(['text-2xl font-bold tracking-tight tabular-nums', 'mt-1' => ! $icon, '-mt-1' => $icon])>{{ $value }}</p>
    @if ($hint)
        <p @class(['mt-1 text-xs', 'text-white/80' => $tone === 'brand', 'text-stone-500' => $tone !== 'brand'])>{{ $hint }}</p>
    @endif
</div>
