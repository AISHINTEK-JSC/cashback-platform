@props(['name' => setting('site_name')])

{{-- Chữ đầu của tên website màu đậm, phần còn lại màu thương hiệu: "Ví" + "Hoàn Tiền" --}}
<span {{ $attributes->class('flex items-center gap-2 sm:gap-2.5') }}>
    <x-logo-mark class="size-8 shrink-0 drop-shadow-sm sm:size-9" />
    <span class="text-base font-extrabold tracking-tight whitespace-nowrap text-stone-900 sm:text-lg">
        {{ Str::before($name, ' ') }}@if (str_contains($name, ' '))
            <span class="text-brand-600">{{ Str::after($name, ' ') }}</span>
        @endif
    </span>
</span>
