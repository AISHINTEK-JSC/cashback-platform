<x-layouts.dashboard title="Link hoàn tiền">
    <div class="card mb-6 p-5">
        <livewire:link-generator />
    </div>

    @if ($links->isEmpty())
        <x-empty-state icon="link" title="Bạn chưa tạo link nào">Dán link sản phẩm ở ô phía trên để lấy link hoàn tiền.</x-empty-state>
    @else
        <div class="space-y-3">
            @foreach ($links as $link)
                <div class="card flex flex-col gap-4 p-4 sm:flex-row sm:items-center" wire:key="link-{{ $link->id }}">
                    @if ($link->product_image)
                        <img src="{{ $link->product_image }}" alt="" class="size-16 rounded-xl object-cover">
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-platform-badge :platform="$link->platform" />
                            <span class="text-xs text-stone-500">{{ $link->created_at->format('d/m/Y H:i') }} · {{ $link->click_count }} lượt mở · {{ $link->orders_count }} đơn</span>
                        </div>
                        <p class="mt-1 truncate font-medium text-stone-900">{{ $link->product_name ?? $link->original_url }}</p>
                        @if ($link->estimated_rate)
                            <p class="text-sm text-emerald-700">Hoàn dự kiến ~{{ rtrim(rtrim(number_format((float) $link->estimated_rate, 2, ',', '.'), '0'), ',') }}%</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <x-copy-button :text="$link->redirectUrl()" label="Copy link" />
                        <a href="{{ $link->redirectUrl() }}" target="_blank" rel="noopener" class="btn btn-primary">Mua</a>
                        <form method="POST" action="{{ route('dashboard.saved-products.store') }}">
                            @csrf
                            <input type="hidden" name="tracking_link_id" value="{{ $link->id }}">
                            <button type="submit" class="btn btn-ghost !px-2.5" title="Lưu sản phẩm" aria-label="Lưu sản phẩm"><x-heroicon-o-heart class="size-5" /></button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $links->links() }}</div>
    @endif
</x-layouts.dashboard>
