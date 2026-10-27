@php
    $suggestions = [
        ['route' => 'vouchers.index', 'icon' => 'ticket', 'label' => 'Mã giảm giá', 'description' => 'Voucher mới nhất của các sàn'],
        ['route' => 'rates', 'icon' => 'receipt-percent', 'label' => 'Tỷ lệ hoàn tiền', 'description' => 'Mức hoàn theo từng sàn'],
        ['route' => 'pages.guide', 'icon' => 'book-open', 'label' => 'Hướng dẫn', 'description' => 'Cách nhận tiền hoàn'],
        ['route' => 'pages.faq', 'icon' => 'question-mark-circle', 'label' => 'Câu hỏi thường gặp', 'description' => 'Giải đáp nhanh thắc mắc'],
    ];
@endphp

<x-layouts.app title="Không tìm thấy trang" noindex>
    <section class="relative overflow-hidden border-b border-stone-200 bg-gradient-to-b from-brand-50 to-stone-50">
        <div class="container-page flex flex-col items-center py-16 text-center sm:py-24">
            <p class="bg-gradient-to-br from-brand-400 to-brand-700 bg-clip-text text-8xl font-extrabold tracking-tighter text-transparent select-none sm:text-9xl" aria-hidden="true">404</p>
            <span class="-mt-6 grid size-16 place-items-center rounded-2xl bg-surface text-brand-600 shadow-sm ring-1 ring-stone-200 sm:-mt-8">
                <x-heroicon-o-map class="size-8" />
            </span>

            <h1 class="mt-6 text-3xl font-bold tracking-tight text-balance text-stone-900 sm:text-4xl">Không tìm thấy trang</h1>
            <p class="mt-3 max-w-xl text-stone-600">Trang bạn tìm có thể đã bị xoá, đổi địa chỉ hoặc link bị gõ sai. Đừng lo, tiền hoàn của bạn vẫn an toàn trong ví.</p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="btn btn-primary">
                    <x-heroicon-o-home class="size-5" /> Về trang chủ
                </a>
                @auth
                    <a href="{{ route('dashboard.index') }}" class="btn btn-secondary">
                        <x-heroicon-o-wallet class="size-5 text-brand-600" /> Ví của tôi
                    </a>
                @else
                    <button type="button" onclick="history.length > 1 ? history.back() : location.assign('{{ route('home') }}')" class="btn btn-secondary">
                        <x-heroicon-o-arrow-left class="size-5" /> Quay lại
                    </button>
                @endauth
            </div>
        </div>
    </section>

    <section class="container-page pt-10">
        <p class="text-center text-sm font-semibold text-stone-500">Có thể bạn đang tìm</p>
        <ul class="mx-auto mt-4 grid max-w-4xl gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($suggestions as $item)
                <li>
                    <a href="{{ route($item['route']) }}" class="card group flex h-full items-start gap-3 p-4 transition hover:border-brand-200">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="size-5" />
                        </span>
                        <span class="min-w-0">
                            <span class="block font-semibold text-stone-900 group-hover:text-brand-700">{{ $item['label'] }}</span>
                            <span class="mt-0.5 block text-sm text-stone-500">{{ $item['description'] }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.app>
