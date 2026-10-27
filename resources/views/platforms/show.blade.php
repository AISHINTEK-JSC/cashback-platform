@php
    $percent = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',').'%';
    $name = $platform->name;
    $rateLabel = $maxCashbackRate ? $percent($maxCashbackRate) : null;
    $title = $comingSoon ? "Hoàn tiền {$name} sắp ra mắt" : "Hoàn tiền {$name}".($rateLabel ? " – nhận lại đến {$rateLabel} mỗi đơn" : '');
    $description = $comingSoon
        ? "Hoàn tiền khi mua {$name} sắp có mặt trên ".setting('site_name').'. Đăng ký để không bỏ lỡ.'
        : "Mua {$name} qua link hoàn tiền của ".setting('site_name').' để nhận lại '.($rateLabel ? "đến {$rateLabel}" : 'tiền').' giá trị đơn hàng, rút về ngân hàng. Giá không đổi, vẫn dùng được mã giảm giá.';
    $faqs = $comingSoon ? [] : [
        "Mua {$name} qua link hoàn tiền có đắt hơn không?" => "Không. Giá sản phẩm, mã giảm giá và quyền lợi của bạn trên {$name} giữ nguyên. Tiền hoàn đến từ hoa hồng {$name} trả cho chúng tôi và được chia lại cho bạn.",
        "Bao lâu thì tiền hoàn {$name} về ví?" => "Đơn thường được ghi nhận sau vài phút đến vài giờ. {$name} chốt hoa hồng sau khoảng {$platform->lock_days} ngày kể từ khi đơn hoàn tất, lúc đó tiền chuyển sang số dư có thể rút về ngân hàng.",
        "Có dùng được mã giảm giá {$name} khi mua qua link hoàn tiền không?" => "Có. Bạn vẫn áp mã giảm giá, mã freeship của {$name} như bình thường. Mức hoàn tính trên số tiền thực trả sau giảm giá.",
        "Đơn {$name} bị huỷ hoặc trả hàng thì sao?" => "Đơn bị huỷ, trả hàng hoặc hoàn tiền sẽ không được tính hoàn tiền. Nếu tiền đã vào số dư, hệ thống sẽ thu hồi lại khoản tương ứng.",
        "Vì sao số tiền hoàn khác dự kiến?" => "Hoa hồng {$name} khác nhau theo ngành hàng, sản phẩm và chương trình khuyến mãi tại thời điểm mua. Số tiền chính xác hiện trong mục Đơn hàng sau khi đơn được ghi nhận.",
    ];
@endphp

<x-layouts.app :title="$title" :description="$description" :image="$platform->logoUrl()" :noindex="$comingSoon">
    <x-slot:head>
        <x-json-ld :data="[
            '@graph' => array_values(array_filter([
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Hoàn tiền '.$name, 'item' => route('platforms.show', $platform)],
                    ],
                ],
                $faqs ? [
                    '@type' => 'FAQPage',
                    'mainEntity' => collect($faqs)->map(fn ($answer, $question) => ['@type' => 'Question', 'name' => $question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer]])->values()->all(),
                ] : null,
            ])),
        ]" />
    </x-slot:head>

    {{-- Hero: logo, mức hoàn tối đa và ô tạo link ngay trên trang --}}
    <section class="border-b border-stone-200 bg-gradient-to-b from-brand-50 to-stone-50">
        <div class="container-page py-12 sm:py-16">
            <nav class="text-sm text-stone-500">
                <a href="{{ route('home') }}" class="hover:text-stone-900">Trang chủ</a>
                <span class="mx-1">/</span>
                <span class="text-stone-700">Hoàn tiền {{ $name }}</span>
            </nav>

            <div class="mt-6 flex flex-col items-center text-center">
                <span class="grid h-20 w-36 place-items-center overflow-hidden rounded-2xl bg-white px-3 shadow-sm ring-1 ring-stone-200">
                    @if ($platform->logoUrl())
                        <img src="{{ $platform->logoUrl() }}" alt="{{ $name }}" class="max-h-14 w-full object-contain">
                    @else
                        <x-platform-badge :platform="$platform" />
                    @endif
                </span>
                <h1 class="mt-5 max-w-3xl text-3xl font-bold tracking-tight text-balance text-stone-900 sm:text-4xl">
                    Hoàn tiền {{ $name }}
                    @if ($rateLabel)
                        <span class="block text-brand-600 sm:inline">đến {{ $rateLabel }}</span>
                    @endif
                </h1>

                @if ($comingSoon)
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-stone-600">Hoàn tiền khi mua {{ $name }} đang được chuẩn bị và sẽ sớm ra mắt. Trong lúc chờ, bạn có thể nhận hoàn tiền ở các sàn đang hỗ trợ bên dưới.</p>
                    <span class="mt-5 inline-flex items-center gap-2 rounded-full bg-surface px-3 py-1 text-sm font-medium text-stone-700 ring-1 ring-stone-200"><x-heroicon-o-clock class="size-4" /> Sắp ra mắt</span>
                @else
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-stone-600">Dán link sản phẩm {{ $name }} để lấy link hoàn tiền. Mua qua link đó như bình thường, tiền hoàn tự về ví của bạn.</p>
                    <div class="mt-8 w-full max-w-2xl">
                        <livewire:link-generator />
                    </div>
                @endif
            </div>
        </div>
    </section>

    @unless ($comingSoon)
        {{-- Số liệu chính của sàn --}}
        <section class="container-page py-10">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat-card label="Hoàn tối đa" icon="arrow-trending-up" :value="$rateLabel ?? 'Theo sản phẩm'" tone="brand" />
                <x-stat-card label="Bạn nhận" icon="receipt-percent" :value="$userRate !== null ? $percent($userRate).' hoa hồng' : 'Đang cập nhật'" hint="Phần hoa hồng sàn trả được chia lại cho bạn" />
                <x-stat-card label="Chốt đơn sau" icon="clock" :value="'~'.$platform->lock_days.' ngày'" hint="Sau đó tiền chuyển sang có thể rút" />
                <x-stat-card label="Mã giảm giá" icon="ticket" :value="$voucherCount > 0 ? $voucherCount.' mã đang có' : 'Đang cập nhật'" />
            </div>
        </section>

        {{-- Cách nhận hoàn tiền --}}
        <section class="container-page py-6">
            <h2 class="text-2xl font-bold text-stone-900">Cách nhận hoàn tiền {{ $name }}</h2>
            <ol class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ([
                    ['icon' => 'link', 'title' => "Sao chép link sản phẩm {$name}", 'body' => "Trong app hoặc web {$name}, mở sản phẩm cần mua, bấm Chia sẻ → Sao chép liên kết. Trên Android đã cài ứng dụng ".setting('site_name').", chọn thẳng ".setting('site_name')." trong bảng chia sẻ."],
                    ['icon' => 'cursor-arrow-rays', 'title' => 'Lấy link hoàn tiền', 'body' => 'Dán link vào ô phía trên rồi bấm Lấy link hoàn tiền. Hệ thống cho biết mức hoàn dự kiến của sản phẩm.'],
                    ['icon' => 'shopping-bag', 'title' => 'Mở link và đặt hàng ngay', 'body' => "Mở link hoàn tiền, đặt hàng trên {$name} trong cùng phiên, không bấm link tiếp thị khác. Đơn tự ghi nhận vào ví của bạn."],
                ] as $i => $step)
                    <li class="card p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-600">
                                <x-dynamic-component :component="'heroicon-o-'.$step['icon']" class="size-6" />
                            </span>
                            <span class="text-sm font-semibold text-brand-600">Bước {{ $i + 1 }}</span>
                        </div>
                        <h3 class="mt-4 font-semibold text-stone-900">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Tỷ lệ theo ngành hàng --}}
        <section class="container-page py-10">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-stone-900">Tỷ lệ hoàn tiền {{ $name }}</h2>
                    <p class="mt-1 text-sm text-stone-600">Phần hoa hồng {{ $name }} trả được chia lại cho bạn và người giới thiệu.</p>
                </div>
                <a href="{{ route('rates') }}" class="text-sm font-semibold text-brand-700 hover:underline">Xem tỷ lệ tất cả các sàn</a>
            </div>
            <div class="card mt-4 overflow-x-auto">
                <table class="table-base">
                    <thead><tr><th>Ngành hàng</th><th class="text-right">Bạn nhận</th><th class="text-right">F1</th><th class="text-right">F2</th></tr></thead>
                    <tbody>
                        @forelse ($rules as $rule)
                            <tr>
                                <td>{{ $rule->category_code ?? 'Mặc định' }}</td>
                                <td class="text-right font-semibold text-emerald-700">{{ $percent($rule->user_rate) }}</td>
                                <td class="text-right">{{ $percent($rule->f1_rate) }}</td>
                                <td class="text-right">{{ $percent($rule->f2_rate) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-stone-500">Đang cập nhật.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($vouchers->isNotEmpty())
            <section class="container-page py-6">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-2xl font-bold text-stone-900">Mã giảm giá {{ $name }}</h2>
                    <a href="{{ route('vouchers.platform', $platform) }}" class="text-sm font-semibold text-brand-700 hover:underline">Xem tất cả {{ $voucherCount }} mã</a>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($vouchers as $voucher)
                        @include('partials.voucher-card', ['voucher' => $voucher])
                    @endforeach
                </div>
            </section>
        @endif
    @endunless

    @if (filled($platform->landing_content))
        {{-- Nội dung riêng của từng sàn do admin soạn trong trang quản trị --}}
        <section class="container-page max-w-3xl py-10">
            <div class="prose-content">{!! $platform->landing_content !!}</div>
        </section>
    @endif

    @if ($faqs)
        <section class="container-page max-w-3xl py-10">
            <h2 class="text-2xl font-bold text-stone-900">Câu hỏi thường gặp về hoàn tiền {{ $name }}</h2>
            <div class="mt-6 space-y-3">
                @foreach ($faqs as $question => $answer)
                    <details class="card group p-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-stone-900">
                            {{ $question }}
                            <x-heroicon-o-chevron-right class="size-5 shrink-0 text-stone-400 transition group-open:rotate-90" />
                        </summary>
                        <p class="mt-3 text-stone-600">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    @endif

    @if ($posts->isNotEmpty())
        <section class="container-page py-10">
            <h2 class="text-2xl font-bold text-stone-900">Mẹo mua sắm {{ $name }}</h2>
            <div class="mt-4 grid gap-6 md:grid-cols-3">
                @foreach ($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @endforeach
            </div>
        </section>
    @endif

    @if ($otherPlatforms->isNotEmpty())
        <section class="container-page pt-6 pb-4">
            <h2 class="text-xl font-bold text-stone-900">Hoàn tiền ở các sàn khác</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($otherPlatforms as $other)
                    <a href="{{ route('platforms.show', $other) }}" class="inline-flex items-center rounded-full bg-surface px-3 py-1.5 text-sm font-medium text-stone-700 ring-1 ring-stone-200 transition hover:text-brand-700 hover:ring-brand-300">
                        Hoàn tiền {{ $other->name }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>
