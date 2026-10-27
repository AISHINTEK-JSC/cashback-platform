<div @if ($autoGenerate) wire:init="generateShared" @endif>
    @if ($link)
        <div class="rounded-2xl border border-emerald-200 bg-surface p-4 text-left shadow-sm sm:p-5">
            <div class="flex items-start gap-4">
                @if ($link->product_image)
                    <img src="{{ $link->product_image }}" alt="" class="size-20 shrink-0 rounded-xl object-cover">
                @else
                    <span class="grid size-20 shrink-0 place-items-center rounded-xl bg-stone-100 text-stone-400">
                        <x-heroicon-o-shopping-bag class="size-8" />
                    </span>
                @endif
                <div class="min-w-0 flex-1">
                    <x-platform-badge :platform="$link->platform" />
                    <p class="mt-1 line-clamp-2 font-semibold text-stone-900">{{ $link->product_name ?? 'Sản phẩm của bạn' }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 text-sm">
                        @if ($link->price)
                            <span class="text-stone-600">{{ vnd($link->price) }}</span>
                        @endif
                        <span class="font-semibold text-emerald-700">
                            {{ $link->estimated_rate ? 'Hoàn dự kiến ~'.rtrim(rtrim(number_format((float) $link->estimated_rate, 2, ',', '.'), '0'), ',').'%' : 'Có hoàn tiền' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2 rounded-xl bg-stone-100 p-2 pl-3">
                <span class="min-w-0 flex-1 truncate font-mono text-sm text-stone-700">{{ $link->redirectUrl() }}</span>
                <x-copy-button :text="$link->redirectUrl()" class="!py-2" />
            </div>

            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <a href="{{ $link->redirectUrl() }}" target="_blank" rel="noopener" class="btn btn-primary">
                    Mua ngay trên {{ $link->platform->name }}
                    <x-heroicon-o-arrow-top-right-on-square class="size-4" />
                </a>
                <button type="button" wire:click="resetForm" class="btn btn-secondary">Lấy link khác</button>
            </div>

            <p class="mt-3 flex items-start gap-2 text-xs text-stone-500">
                <x-heroicon-o-exclamation-triangle class="size-4 shrink-0 text-amber-500" />
                Mở link rồi mua ngay trong phiên đó. Giỏ hàng nên trống trước khi thêm sản phẩm, không bấm link tiếp thị khác trước khi đặt đơn.
            </p>
        </div>
    @else
        <form wire:submit="generate" @class(['flex gap-2', 'flex-col sm:flex-row' => ! $compact])>
            <div class="relative flex-1">
                <x-heroicon-o-link class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-stone-400" />
                <input
                    type="url"
                    wire:model="url"
                    placeholder="Dán link sản phẩm cần hoàn tiền..."
                    class="input !rounded-2xl !py-3.5 !pr-24 !pl-12 !text-base"
                    autocomplete="off"
                    inputmode="url"
                >
                <button
                    type="button"
                    x-data
                    x-show="navigator.clipboard && navigator.clipboard.readText"
                    @click="navigator.clipboard.readText().then(t => $wire.set('url', t.trim()))"
                    class="absolute top-1/2 right-2 -translate-y-1/2 rounded-lg bg-stone-100 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-200"
                >Dán</button>
            </div>
            <button type="submit" class="btn btn-primary !rounded-2xl !px-6 !py-3.5 !text-base" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generate, generateShared">Lấy link hoàn tiền</span>
                <span wire:loading wire:target="generate, generateShared">Đang tạo link...</span>
            </button>
        </form>
        @error('url')
            <p class="field-error text-left">{{ $message }}</p>
        @enderror
    @endif
</div>
