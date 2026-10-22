<x-layouts.dashboard title="Sản phẩm đã lưu">
    @if ($products->isEmpty())
        <x-empty-state icon="heart" title="Chưa lưu sản phẩm nào" action="Xem link đã tạo" :action-url="route('dashboard.links.index')">
            Bấm biểu tượng trái tim ở trang Link hoàn tiền để lưu sản phẩm muốn mua sau.
        </x-empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($products as $product)
                <div class="card flex flex-col overflow-hidden">
                    @if ($product->image)
                        <img src="{{ $product->image }}" alt="" class="aspect-square w-full object-cover" loading="lazy">
                    @endif
                    <div class="flex flex-1 flex-col p-4">
                        <x-platform-badge :platform="$product->platform" class="self-start" />
                        <p class="mt-2 line-clamp-2 font-medium text-stone-900">{{ $product->name }}</p>
                        @if ($product->price)<p class="mt-1 text-sm text-stone-600">{{ vnd($product->price) }}</p>@endif
                        <div class="mt-auto flex gap-2 pt-4">
                            <a href="{{ route('dashboard.index', ['link' => $product->url]) }}" class="btn btn-primary flex-1">Lấy link hoàn tiền</a>
                            <form method="POST" action="{{ route('dashboard.saved-products.destroy', $product) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost !px-2.5 text-red-600" aria-label="Bỏ lưu"><x-heroicon-o-trash class="size-5" /></button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
</x-layouts.dashboard>
