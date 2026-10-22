<x-layouts.app title="Mã giảm giá" description="Tổng hợp mã giảm giá, freeship Shopee, TikTok Shop, Lazada mới nhất, cập nhật hằng ngày.">
    <div class="container-page py-10">
        <h1 class="text-3xl font-bold text-stone-900">Mã giảm giá</h1>
        <p class="mt-2 text-stone-600">Lưu mã rồi mua qua link hoàn tiền để vừa được giảm giá vừa nhận hoàn tiền.</p>

        <div class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('vouchers.index') }}" @class(['btn !py-2', 'btn-primary' => ! $current, 'btn-secondary' => $current])>Tất cả</a>
            @foreach ($platforms as $platform)
                <a href="{{ route('vouchers.index', ['san' => $platform->code]) }}" @class(['btn !py-2', 'btn-primary' => $current?->is($platform), 'btn-secondary' => ! $current?->is($platform)])>{{ $platform->name }}</a>
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
    </div>
</x-layouts.app>
