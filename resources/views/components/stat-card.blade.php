@props(['label', 'value', 'hint' => null, 'tone' => 'default'])

<div {{ $attributes->class([
    'rounded-2xl border p-5',
    'border-brand-600 bg-brand-600 text-white' => $tone === 'brand',
    'border-stone-200 bg-white' => $tone !== 'brand',
]) }}>
    <p @class(['text-sm', 'text-brand-100' => $tone === 'brand', 'text-stone-500' => $tone !== 'brand'])>{{ $label }}</p>
    <p class="mt-1 text-2xl font-bold tabular-nums">{{ $value }}</p>
    @if ($hint)
        <p @class(['mt-1 text-xs', 'text-brand-100' => $tone === 'brand', 'text-stone-500' => $tone !== 'brand'])>{{ $hint }}</p>
    @endif
</div>
