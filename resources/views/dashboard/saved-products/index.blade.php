<x-layouts.dashboard title="Sản phẩm đã lưu" description="Sản phẩm bạn muốn mua sau, lấy link hoàn tiền khi sẵn sàng đặt hàng.">
    @if ($products->isEmpty())
        <x-empty-state icon="heart" title="Chưa lưu sản phẩm nào" action="Xem link đã tạo" :action-url="route('dashboard.links.index')">
            Bấm biểu tượng trái tim ở trang Link hoàn tiền để lưu sản phẩm muốn mua sau.
        </x-empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($products as $product)
                <div class="card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="relative aspect-square bg-stone-100">
                        @if ($product->image)
                            <img src="{{ $product->image }}" alt="" class="size-full object-cover" loading="lazy">
                        @else
                            <span class="grid size-full place-items-center text-stone-300"><x-heroicon-o-photo class="size-12" /></span>
                        @endif
                        <x-platform-badge :platform="$product->platform" class="absolute top-3 left-3 shadow-sm" />
                    </div>
                    <div class="flex flex-1 flex-col p-4">
                        <p class="line-clamp-2 font-medium text-stone-900">{{ $product->name }}</p>
                        @if ($product->price)<p class="mt-1 font-semibold text-brand-700 tabular-nums">{{ vnd($product->price) }}</p>@endif
                        <div class="mt-auto flex gap-2 pt-4">
                            <a href="{{ route('dashboard.index', ['link' => $product->url]) }}" class="btn btn-primary flex-1">Lấy link hoàn tiền</a>
                            <form method="POST" action="{{ route('dashboard.saved-products.destroy', $product) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-secondary !px-2.5 text-stone-500 hover:!bg-red-50 hover:!text-red-600" title="Bỏ lưu" aria-label="Bỏ lưu"><x-heroicon-o-trash class="size-5" /></button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
</x-layouts.dashboard>
