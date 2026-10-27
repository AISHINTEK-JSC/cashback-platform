@props(['url', 'title'])

@php
    $encodedUrl = rawurlencode($url);
    $encodedTitle = rawurlencode($title);
    $networks = [
        ['label' => 'Facebook', 'href' => "https://www.facebook.com/sharer/sharer.php?u={$encodedUrl}"],
        ['label' => 'X', 'href' => "https://x.com/intent/post?url={$encodedUrl}&text={$encodedTitle}"],
        ['label' => 'Telegram', 'href' => "https://t.me/share/url?url={$encodedUrl}&text={$encodedTitle}"],
    ];
@endphp

{{-- Cùng kiểu nút sáng/tối và icon liên hệ ở footer: btn-ghost, icon một màu --}}
<div {{ $attributes->class(['flex flex-wrap items-center gap-1']) }}>
    <span class="mr-1 text-sm font-medium text-stone-500">Chia sẻ:</span>

    @foreach ($networks as $network)
        <a href="{{ $network['href'] }}" target="_blank" rel="noopener" class="btn btn-ghost !px-2.5" aria-label="Chia sẻ lên {{ $network['label'] }}" title="Chia sẻ lên {{ $network['label'] }}">
            @switch($network['label'])
                @case('Facebook')
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3h-2.5A4.5 4.5 0 0 0 10 7.5V10H7v3.5h3V21h3.5v-7.5h3l.5-3.5h-3.5V7.75c0-.69.56-1.25 1.25-1.25H17Z"/></svg>
                    @break
                @case('X')
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg>
                    @break
                @case('Telegram')
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.5 4.5 2.5 11.8l6.2 2.2 2.3 6.5 3.4-4.1 5 3.7 2.1-15.6Z"/><path d="m8.7 14 12.8-9.5"/></svg>
                    @break
            @endswitch
        </a>
    @endforeach

    <button type="button" x-data="copyable(@js($url))" @click="copy()" class="btn btn-ghost !px-2.5" :aria-label="copied ? 'Đã sao chép link' : 'Sao chép link'" :title="copied ? 'Đã sao chép link' : 'Sao chép link'" aria-label="Sao chép link">
        <x-heroicon-o-link class="size-5" x-show="!copied" />
        <x-heroicon-o-check class="size-5 text-emerald-600" x-show="copied" x-cloak />
        <span x-show="copied" x-cloak class="text-sm">Đã sao chép</span>
    </button>

    {{-- Trên điện thoại: mở bảng chia sẻ của hệ điều hành (Zalo, Messenger...) --}}
    <button type="button" x-data="{ canShare: 'share' in navigator }" x-show="canShare" x-cloak @click="navigator.share({ title: @js($title), url: @js($url) }).catch(() => {})" class="btn btn-ghost !px-2.5" aria-label="Chia sẻ qua ứng dụng khác" title="Chia sẻ qua ứng dụng khác">
        <x-heroicon-o-share class="size-5" />
    </button>
</div>
