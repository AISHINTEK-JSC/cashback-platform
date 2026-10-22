<x-layouts.app title="Hướng dẫn nhận hoàn tiền" description="Hướng dẫn lấy link hoàn tiền Shopee, TikTok Shop, Lazada và những lưu ý để đơn được ghi nhận.">
    <div class="container-page max-w-3xl py-10">
        <h1 class="text-3xl font-bold text-stone-900">Hướng dẫn nhận hoàn tiền</h1>

        <div class="prose-content mt-6">
            <h2>1. Lấy link sản phẩm</h2>
            <p>Trên ứng dụng Shopee, TikTok Shop hoặc Lazada, mở sản phẩm muốn mua, bấm <strong>Chia sẻ</strong> rồi chọn <strong>Sao chép liên kết</strong>.</p>

            <h2>2. Tạo link hoàn tiền</h2>
            <p>Dán link vào ô trên trang chủ hoặc trang Tổng quan và bấm <strong>Lấy link hoàn tiền</strong>. Hệ thống hiển thị % hoàn dự kiến (nếu sàn cung cấp) và link dành riêng cho bạn.</p>

            <h2>3. Mua hàng qua link</h2>
            <p>Bấm <strong>Mua ngay</strong>, link sẽ mở ứng dụng của sàn. Thêm sản phẩm vào giỏ và thanh toán như bình thường. Bạn có thể dùng mã giảm giá của sàn.</p>

            <h2>4. Theo dõi và rút tiền</h2>
            <ul>
                <li><strong>Đã ghi nhận:</strong> đơn xuất hiện trong mục Đơn hàng sau vài giờ, tiền nằm ở số dư chờ duyệt.</li>
                <li><strong>Sàn đã xác nhận:</strong> đơn giao thành công, đang chờ hết thời gian đổi trả.</li>
                <li><strong>Có thể rút:</strong> sàn chốt hoa hồng, tiền chuyển sang số dư khả dụng.</li>
                <li>Thêm tài khoản ngân hàng/ví và tạo yêu cầu rút tiền, chúng tôi xử lý trong 1-3 ngày làm việc.</li>
            </ul>

            <h2>Lưu ý quan trọng</h2>
            <ul>
                <li>Xoá sản phẩm khỏi giỏ hàng trước khi bấm link hoàn tiền, rồi thêm lại sau khi mở link.</li>
                <li>Tắt Adblock/trình chặn quảng cáo, không dùng chế độ ẩn danh.</li>
                <li>Không bấm link tiếp thị, livestream, video của người khác sau khi mở link hoàn tiền.</li>
                <li>Mua trong vòng 7 ngày kể từ lúc mở link. Nên tạo link mới cho mỗi lần mua.</li>
                <li>Đơn huỷ, hoàn hàng/hoàn tiền hoặc sàn đánh giá không hợp lệ sẽ không được hoàn tiền.</li>
                <li>Không tự tạo nhiều tài khoản để giới thiệu chính mình; tài khoản vi phạm sẽ bị khoá và thu hồi tiền.</li>
            </ul>

            <h2>Không thấy đơn?</h2>
            <p>Đợi tối đa 24 giờ. Sau đó nếu vẫn chưa thấy, hãy gửi <a href="{{ route('dashboard.tickets.create', ['type' => 'missing_order']) }}">khiếu nại đơn thiếu</a> kèm mã đơn hàng trên sàn.</p>
        </div>
    </div>
</x-layouts.app>
