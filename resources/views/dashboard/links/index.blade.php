<x-layouts.dashboard title="Link hoàn tiền" description="Tạo link hoàn tiền cho sản phẩm và theo dõi lượt mở, đơn hàng của từng link.">
    <div class="card mb-8 p-5 sm:p-6">
        <livewire:link-generator />
    </div>

    <x-section-header title="Link đã tạo" />

    @if ($links->isEmpty())
        <x-empty-state icon="link" title="Bạn chưa tạo link nào">Dán link sản phẩm ở ô phía trên để lấy link hoàn tiền.</x-empty-state>
    @else
        <div class="space-y-3">
            @foreach ($links as $link)
                <div class="card flex flex-col gap-4 p-4 transition hover:border-stone-300 sm:flex-row sm:items-center" wire:key="link-{{ $link->id }}">
                    <div class="flex min-w-0 flex-1 items-center gap-4">
                        @if ($link->product_image)
                            <img src="{{ $link->product_image }}" alt="" class="size-16 shrink-0 rounded-xl object-cover ring-1 ring-stone-200" loading="lazy">
                        @else
                            <span class="grid size-16 shrink-0 place-items-center rounded-xl bg-stone-100 text-stone-400">
                                <x-heroicon-o-shopping-bag class="size-7" />
                            </span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-platform-badge :platform="$link->platform" />
                                @if ($link->estimated_rate)
                                    <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Hoàn ~{{ rtrim(rtrim(number_format((float) $link->estimated_rate, 2, ',', '.'), '0'), ',') }}%</span>
                                @endif
                            </div>
                            <p class="mt-1 truncate font-medium text-stone-900">{{ $link->product_name ?? $link->original_url }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-stone-500">
                                <span class="inline-flex items-center gap-1"><x-heroicon-o-calendar class="size-3.5" />{{ $link->created_at->format('d/m/Y H:i') }}</span>
                                <span class="inline-flex items-center gap-1"><x-heroicon-o-cursor-arrow-rays class="size-3.5" />{{ $link->click_count }} lượt mở</span>
                                <span class="inline-flex items-center gap-1"><x-heroicon-o-shopping-bag class="size-3.5" />{{ $link->orders_count }} đơn</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <x-copy-button :text="$link->redirectUrl()" label="Copy link" class="flex-1 sm:flex-none" />
                        <a href="{{ $link->redirectUrl() }}" target="_blank" rel="noopener" class="btn btn-primary flex-1 sm:flex-none">
                            Mua <x-heroicon-o-arrow-top-right-on-square class="size-4" />
                        </a>
                        <form method="POST" action="{{ route('dashboard.saved-products.store') }}">
                            @csrf
                            <input type="hidden" name="tracking_link_id" value="{{ $link->id }}">
                            <button type="submit" class="btn btn-ghost !px-2.5 hover:text-rose-600" title="Lưu sản phẩm" aria-label="Lưu sản phẩm"><x-heroicon-o-heart class="size-5" /></button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $links->links() }}</div>
    @endif
</x-layouts.dashboard>
