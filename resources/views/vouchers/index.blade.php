@php
    $period = 'tháng '.now()->format('n/Y');
    $rateLabel = $maxCashbackRate ? rtrim(rtrim(number_format($maxCashbackRate, 2, ',', '.'), '0'), ',').'%' : null;
    $heading = $current ? "Mã giảm giá {$current->name} {$period}" : "Mã giảm giá {$period}";
    $description = $current
        ? "Tổng hợp mã giảm giá, mã freeship {$current->name} mới nhất {$period}, cập nhật hằng ngày. Mua qua link hoàn tiền".($rateLabel ? " để được hoàn đến {$rateLabel}" : ' để được hoàn tiền thêm').'.'
        : "Tổng hợp mã giảm giá, freeship ".$platforms->pluck('name')->join(', ')." mới nhất {$period}, cập nhật hằng ngày.";
@endphp

<x-layouts.app :title="$heading" :description="$description">
    <x-slot:head>
        <x-json-ld :data="[
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_filter([
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Mã giảm giá', 'item' => route('vouchers.index')],
                $current ? ['@type' => 'ListItem', 'position' => 3, 'name' => $current->name, 'item' => route('vouchers.platform', $current)] : null,
            ])),
        ]" />
    </x-slot:head>

    <div class="container-page py-10">
        <nav class="text-sm text-stone-500">
            <a href="{{ route('home') }}" class="hover:text-stone-900">Trang chủ</a>
            <span class="mx-1">/</span>
            <a href="{{ route('vouchers.index') }}" class="hover:text-stone-900">Mã giảm giá</a>
            @if ($current)
                <span class="mx-1">/</span>
                <span class="text-stone-700">{{ $current->name }}</span>
            @endif
        </nav>

        <h1 class="mt-3 text-3xl font-bold text-stone-900">{{ $heading }}</h1>
        <p class="mt-2 text-stone-600">
            Lưu mã rồi mua qua link hoàn tiền để vừa được giảm giá vừa nhận hoàn tiền.
            @if ($rateLabel)
                Đơn {{ $current->name }} được hoàn đến <strong class="text-brand-700">{{ $rateLabel }}</strong>.
            @endif
        </p>

        <div class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('vouchers.index') }}" @class(['btn !py-2', 'btn-primary' => ! $current, 'btn-secondary' => $current])>Tất cả</a>
            @foreach ($platforms as $platform)
                <a href="{{ route('vouchers.platform', $platform) }}" @class(['btn !py-2', 'btn-primary' => $current?->is($platform), 'btn-secondary' => ! $current?->is($platform)])>{{ $platform->name }}</a>
            @endforeach
        </div>

        @if ($vouchers->isEmpty())
            <x-empty-state class="mt-8" icon="ticket" title="Chưa có mã giảm giá">Quay lại sau nhé, mã mới được cập nhật hằng ngày.</x-empty-state>
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($vouchers as $voucher)
                    @include('partials.voucher-card', ['voucher' => $voucher])
                @endforeach
            </div>
            <div class="mt-8">{{ $vouchers->links() }}</div>
        @endif

        <section class="card mt-10 p-6 sm:p-8">
            <h2 class="text-xl font-bold text-stone-900">Cách dùng mã giảm giá {{ $current?->name }} và nhận thêm hoàn tiền</h2>
            <ol class="mt-4 list-decimal space-y-2 pl-6 text-stone-700">
                <li>Bấm <strong>Lưu mã</strong> để sao chép mã, hoặc bấm <strong>Đến trang lấy mã</strong> để lưu mã vào ví voucher trên ứng dụng.</li>
                <li>Dán link sản phẩm muốn mua vào ô bên dưới để lấy link hoàn tiền, rồi mở link đó và thêm sản phẩm vào giỏ.</li>
                <li>Ở bước thanh toán, chọn hoặc nhập mã đã lưu. Kiểm tra điều kiện đơn tối thiểu và mức giảm tối đa của mã.</li>
                <li>Đặt hàng như bình thường. Đơn được ghi nhận trong mục <a href="{{ route('dashboard.orders.index') }}" class="text-brand-700 underline">Đơn hàng hoàn tiền</a>, tiền hoàn về ví sau khi sàn đối soát.</li>
            </ol>
            <p class="mt-4 text-sm text-stone-600">Mã giảm giá có số lượng giới hạn và có thể hết lượt trước hạn sử dụng. Xem chi tiết mức hoàn theo ngành hàng ở trang <a href="{{ route('rates') }}" class="text-brand-700 underline">tỷ lệ hoàn tiền</a>.</p>

            <div class="mx-auto mt-6 max-w-2xl">
                <livewire:link-generator />
            </div>
        </section>

        @if ($related->isNotEmpty())
            <section class="mt-10">
                <h2 class="text-xl font-bold text-stone-900">Mẹo mua sắm {{ $current?->name }}</h2>
                <div class="mt-4 grid gap-6 md:grid-cols-3">
                    @foreach ($related as $post)
                        @include('partials.post-card', ['post' => $post])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
