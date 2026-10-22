@php
    $faqs = [
        'Hoàn tiền là gì, tiền từ đâu ra?' => 'Các sàn TMĐT trả hoa hồng cho đối tác tiếp thị liên kết khi có người mua qua link của đối tác. Chúng tôi là đối tác đó và chia lại phần lớn hoa hồng cho bạn.',
        'Mua qua link hoàn tiền có đắt hơn không?' => 'Không. Giá sản phẩm, mã giảm giá và quyền lợi của bạn trên sàn không thay đổi.',
        'Bao lâu thì đơn được ghi nhận?' => 'Thường từ vài phút tới vài giờ sau khi đặt, tuỳ tốc độ cập nhật báo cáo của sàn.',
        'Bao lâu thì rút được tiền?' => 'Sau khi sàn chốt hoa hồng, thường 30-60 ngày kể từ ngày mua tuỳ sàn. Thời gian dự kiến được hiển thị ở từng đơn.',
        'Vì sao số tiền hoàn khác dự kiến?' => 'Hoa hồng thực tế do sàn quyết định theo từng sản phẩm, phí vận chuyển, mã giảm giá và chương trình khuyến mãi tại thời điểm mua.',
        'Rút tiền tối thiểu bao nhiêu?' => 'Tối thiểu '.vnd((int) setting('withdraw_min')).' mỗi lần. Phí rút: '.vnd((int) setting('withdraw_fee')).'.',
        'Giới thiệu bạn bè nhận gì?' => 'Bạn nhận hoa hồng F1 trên mỗi đơn của người bạn mời trực tiếp và hoa hồng F2 trên đơn của người họ mời, xem tỷ lệ ở trang Tỷ lệ hoàn tiền.',
        'Đơn bị huỷ thì sao?' => 'Đơn bị huỷ hoặc hoàn hàng sẽ không được hoàn tiền. Nếu tiền đã vào số dư khả dụng, hệ thống sẽ thu hồi lại.',
    ];
@endphp

<x-layouts.app title="Câu hỏi thường gặp">
    <div class="container-page max-w-3xl py-10">
        <h1 class="text-3xl font-bold text-stone-900">Câu hỏi thường gặp</h1>
        <div class="mt-8 space-y-3">
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
        <p class="mt-8 text-stone-600">Chưa có câu trả lời? <a href="{{ route('dashboard.tickets.create') }}" class="font-semibold text-brand-700 underline">Gửi yêu cầu hỗ trợ</a>.</p>
    </div>
</x-layouts.app>
