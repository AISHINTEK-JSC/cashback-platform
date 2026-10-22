@props(['text', 'label' => 'Sao chép'])

<button type="button" x-data="copyable(@js($text))" @click="copy()" {{ $attributes->class(['btn btn-secondary']) }}>
    <x-heroicon-o-document-duplicate class="size-4" x-show="!copied" />
    <x-heroicon-o-check class="size-4 text-emerald-600" x-show="copied" x-cloak />
    <span x-text="copied ? 'Đã sao chép' : @js($label)">{{ $label }}</span>
</button>
