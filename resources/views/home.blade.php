<x-layouts.app>
    {{-- Hero: dán link lấy link hoàn tiền --}}
    <section class="relative overflow-hidden border-b border-stone-200 bg-gradient-to-b from-brand-50 to-stone-50">
        <div class="container-page py-14 text-center sm:py-20">
            <p class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm font-medium text-brand-700 ring-1 ring-brand-200">
                <x-heroicon-o-sparkles class="size-4" /> Hoàn tiền thật cho mọi đơn hợp lệ
            </p>
            <h1 class="mx-auto mt-5 max-w-3xl text-4xl font-bold tracking-tight text-balance text-stone-900 sm:text-5xl">
                Mua sắm như bình thường, <span class="text-brand-600">nhận lại tiền</span> cho mỗi đơn hàng
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-stone-600">Dán link sản phẩm Shopee, TikTok Shop, Lazada để lấy link hoàn tiền. Mua qua link đó, tiền hoàn tự động về ví của bạn.</p>

            <div class="mx-auto mt-8 max-w-2xl">
                <livewire:link-generator />
            </div>

            @if ($platforms->isNotEmpty())
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3 text-sm text-stone-600">
                    @foreach ($platforms as $platform)
                        <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 ring-1 ring-stone-200">
                            <x-platform-badge :platform="$platform" />
                            @if ($platform->user_rate)
                                nhận lại {{ rtrim(rtrim(number_format((float) $platform->user_rate, 2, ',', '.'), '0'), ',') }}% hoa hồng
                            @endif
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Thống kê hệ thống --}}
    <section class="container-page -mt-px py-10">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat-card label="Thành viên" :value="number_format($stats['members'], 0, ',', '.')" />
            <x-stat-card label="Đơn hàng được hoàn tiền" :value="number_format($stats['orders'], 0, ',', '.')" />
            <x-stat-card label="Tổng tiền đã hoàn cho thành viên" :value="vnd($stats['paid_out'])" tone="brand" />
        </div>
    </section>

    {{-- Quy trình 3 bước --}}
    <section class="container-page py-10">
        <h2 class="text-center text-2xl font-bold text-stone-900 sm:text-3xl">Nhận hoàn tiền chỉ với 3 bước</h2>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ([
                ['icon' => 'link', 'title' => 'Dán link sản phẩm', 'body' => 'Sao chép link sản phẩm trên ứng dụng Shopee, TikTok Shop hoặc Lazada và dán vào ô phía trên.'],
                ['icon' => 'shopping-bag', 'title' => 'Mua qua link hoàn tiền', 'body' => 'Mở link hoàn tiền, thêm sản phẩm vào giỏ và đặt hàng như bình thường, không cần làm gì thêm.'],
                ['icon' => 'banknotes', 'title' => 'Nhận tiền về tài khoản', 'body' => 'Đơn tự động ghi nhận vào ví. Khi sàn chốt đơn, tiền chuyển sang số dư có thể rút về ngân hàng.'],
            ] as $i => $step)
                <div class="card p-6">
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <x-dynamic-component :component="'heroicon-o-'.$step['icon']" class="size-6" />
                        </span>
                        <span class="text-sm font-semibold text-brand-600">Bước {{ $i + 1 }}</span>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-stone-900">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ $step['body'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="card mt-6 p-6">
            <h3 class="font-semibold text-stone-900">Khi nào tiền về?</h3>
            <ol class="mt-5 grid gap-6 sm:grid-cols-3">
                @foreach ([
                    ['Ngày mua', 'Bạn đặt đơn qua link hoàn tiền.'],
                    ['Ghi nhận', 'Đơn hiện trong mục Đơn hàng sau vài giờ, tiền nằm ở số dư chờ duyệt.'],
                    ['Có thể rút', 'Sau khi sàn chốt đơn (thường 30-60 ngày), tiền chuyển sang số dư khả dụng.'],
                ] as $i => [$title, $body])
                    <li class="relative flex gap-3">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">{{ $i + 1 }}</span>
                        <div>
                            <p class="font-semibold text-stone-900">{{ $title }}</p>
                            <p class="mt-1 text-sm text-stone-600">{{ $body }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Lưu ý khi mua --}}
    <section class="container-page py-10">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 sm:p-8">
            <h2 class="flex items-center gap-2 text-xl font-bold text-amber-950">
                <x-heroicon-o-exclamation-triangle class="size-6" /> Lưu ý để đơn được ghi nhận
            </h2>
            <ul class="mt-4 grid gap-3 text-sm text-amber-950 sm:grid-cols-2">
                <li class="flex gap-2"><x-heroicon-o-check class="mt-0.5 size-4 shrink-0" /> Xoá sản phẩm cần mua khỏi giỏ hàng trước khi bấm link hoàn tiền.</li>
                <li class="flex gap-2"><x-heroicon-o-check class="mt-0.5 size-4 shrink-0" /> Tắt trình chặn quảng cáo (Adblock) khi mở link.</li>
                <li class="flex gap-2"><x-heroicon-o-check class="mt-0.5 size-4 shrink-0" /> Không bấm link tiếp thị/livestream khác sau khi mở link hoàn tiền.</li>
                <li class="flex gap-2"><x-heroicon-o-check class="mt-0.5 size-4 shrink-0" /> Không huỷ đơn rồi đặt lại; đơn huỷ hoặc hoàn hàng sẽ không được hoàn tiền.</li>
            </ul>
            <a href="{{ route('pages.guide') }}" class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-amber-950 underline">Xem hướng dẫn chi tiết <x-heroicon-o-arrow-right class="size-4" /></a>
        </div>
    </section>

    {{-- Mã giảm giá --}}
    @if ($vouchers->isNotEmpty())
        <section class="container-page py-10">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-2xl font-bold text-stone-900">Mã giảm giá hôm nay</h2>
                <a href="{{ route('vouchers.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Xem tất cả</a>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($vouchers as $voucher)
                    @include('partials.voucher-card', ['voucher' => $voucher])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Giới thiệu bạn bè --}}
    <section class="container-page py-10">
        <div class="grid items-center gap-6 rounded-2xl bg-stone-900 p-8 text-white md:grid-cols-[1fr_auto]">
            <div>
                <h2 class="text-2xl font-bold">Mời bạn bè, nhận thêm hoa hồng 2 tầng</h2>
                <p class="mt-2 max-w-2xl text-stone-300">Mỗi đơn hàng của người bạn mời (F1) và người họ mời (F2) đều mang lại thêm hoa hồng cho bạn, trọn đời.</p>
            </div>
            <a href="{{ auth()->check() ? route('dashboard.referrals.index') : route('register') }}" class="btn btn-primary">
                <x-heroicon-o-users class="size-5" /> Lấy link giới thiệu
            </a>
        </div>
    </section>

    {{-- Blog --}}
    @if ($posts->isNotEmpty())
        <section class="container-page py-10">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-2xl font-bold text-stone-900">Mẹo săn sale</h2>
                <a href="{{ route('posts.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Xem blog</a>
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>
