@php
    $siteName = setting('site_name');
    $hotline = setting('support_hotline');
    $zalo = setting('support_zalo');
    $email = setting('support_email');
    $operator = setting('operator_name') ?: 'một cá nhân';
    $address = setting('operator_address');
@endphp

<x-layouts.app title="Chính sách bảo mật" description="{{ $siteName }} thu thập dữ liệu gì, dùng vào việc gì, chia sẻ với ai và quyền của bạn đối với dữ liệu cá nhân.">
    <div class="container-page max-w-3xl py-10">
        <h1 class="text-3xl font-bold text-stone-900">Chính sách bảo mật</h1>
        <p class="mt-2 text-sm text-stone-500">Cập nhật lần cuối: 27/10/2026</p>

        <div class="prose-content mt-6">
            <p>{{ $siteName }} ("chúng tôi") tôn trọng quyền riêng tư của bạn. Chính sách này giải thích chúng tôi thu thập dữ liệu cá nhân nào, dùng vào việc gì, chia sẻ với ai và bạn có những quyền gì, theo Nghị định 13/2023/NĐ-CP về bảo vệ dữ liệu cá nhân. Khi đăng ký tài khoản, bạn đồng ý với việc xử lý dữ liệu như mô tả dưới đây.</p>
            <p><strong>Bên kiểm soát và xử lý dữ liệu:</strong> {{ $siteName }} do {{ $operator }} sở hữu và vận hành{{ $address ? ', địa chỉ '.$address : '' }}{{ $email ? ', email '.$email : '' }}.</p>

            <h2>1. Dữ liệu chúng tôi thu thập</h2>
            <h3>Do bạn cung cấp</h3>
            <ul>
                <li><strong>Thông tin tài khoản:</strong> họ tên, email, số điện thoại (không bắt buộc), mật khẩu (được mã hoá một chiều, chúng tôi không đọc được), ảnh đại diện nếu bạn tải lên.</li>
                <li><strong>Thông tin nhận tiền:</strong> tên ngân hàng/ví điện tử, số tài khoản, tên chủ tài khoản.</li>
                <li><strong>Nội dung hỗ trợ:</strong> tin nhắn và tệp đính kèm bạn gửi trong yêu cầu hỗ trợ.</li>
            </ul>
            <h3>Khi đăng nhập bằng Google</h3>
            <p>Chúng tôi chỉ nhận họ tên, địa chỉ email và mã định danh tài khoản Google của bạn. Chúng tôi không nhận mật khẩu Google, không truy cập danh bạ, email hay dữ liệu khác trong tài khoản Google.</p>
            <h3>Khi liên kết Telegram (không bắt buộc)</h3>
            <p>Nếu bạn bấm Liên kết Telegram và bắt đầu chat với bot {{ '@'.config('services.telegram.bot_username') }}, chúng tôi lưu mã chat (chat ID), tên người dùng Telegram (@username) và thời điểm liên kết, cùng nội dung bạn gửi cho bot (link sản phẩm, lệnh tra cứu) để trả lời bạn. Chúng tôi không đọc được các cuộc trò chuyện khác, danh bạ hay số điện thoại Telegram của bạn.</p>
            <h3>Thu thập tự động</h3>
            <ul>
                <li><strong>Dữ liệu mua sắm:</strong> link sản phẩm bạn tạo, đơn hàng do sàn báo về qua chương trình tiếp thị liên kết (mã đơn, sản phẩm, giá trị, trạng thái, hoa hồng). Chúng tôi không nhận địa chỉ giao hàng hay thông tin thanh toán của bạn trên sàn.</li>
                <li><strong>Dữ liệu kỹ thuật:</strong> địa chỉ IP, loại trình duyệt/thiết bị, mã định danh thiết bị, thời gian đăng nhập và nhật ký thao tác quan trọng (đăng nhập, thêm tài khoản ngân hàng, rút tiền).</li>
                <li><strong>Thống kê truy cập:</strong> số liệu ẩn danh về trang đã xem và nguồn truy cập qua Google Analytics.</li>
            </ul>

            <h2>2. Mục đích sử dụng</h2>
            <ul>
                <li>Tạo link hoàn tiền, ghi nhận đơn hàng, tính và chi trả tiền hoàn, hoa hồng giới thiệu và các khoản thưởng.</li>
                <li>Xác thực tài khoản, chuyển tiền rút về đúng tài khoản của bạn.</li>
                <li>Phát hiện và ngăn chặn gian lận: nhiều tài khoản của cùng một người, dùng chung tài khoản ngân hàng, đơn ảo, truy cập trái phép.</li>
                <li>Gửi thông báo về đơn hàng, số dư, rút tiền, bảo mật tài khoản và trả lời yêu cầu hỗ trợ, trên website và qua Telegram nếu bạn đã liên kết.</li>
                <li>Thống kê, cải thiện website và dịch vụ.</li>
                <li>Tuân thủ nghĩa vụ pháp lý, kế toán, thuế và yêu cầu của cơ quan nhà nước có thẩm quyền.</li>
            </ul>
            <p>Chúng tôi <strong>không bán</strong> dữ liệu cá nhân của bạn và không dùng dữ liệu của bạn cho quảng cáo của bên thứ ba.</p>

            <h2>3. Chia sẻ dữ liệu</h2>
            <p>Chúng tôi chỉ chia sẻ dữ liệu ở mức cần thiết với:</p>
            <ul>
                <li><strong>Sàn thương mại điện tử và mạng tiếp thị liên kết</strong> (như Shopee, TikTok Shop, Lazada, AccessTrade): một mã theo dõi gắn với link hoàn tiền để ghi nhận đơn. Mã này không chứa tên, email hay số điện thoại của bạn.</li>
                <li><strong>Nhà cung cấp hạ tầng:</strong> dịch vụ máy chủ, cơ sở dữ liệu, lưu trữ tệp và gửi email để vận hành website. Họ chỉ xử lý dữ liệu theo chỉ dẫn của chúng tôi.</li>
                <li><strong>Google:</strong> đăng nhập bằng Google và thống kê truy cập Google Analytics.</li>
                <li><strong>Telegram:</strong> khi bạn đã liên kết, tin nhắn giữa bạn và bot (thông báo đơn hàng, số dư, link hoàn tiền) được truyền qua máy chủ của Telegram và chịu thêm chính sách bảo mật của Telegram.</li>
                <li><strong>Ngân hàng/ví điện tử:</strong> thông tin tài khoản nhận tiền khi thực hiện chuyển khoản cho bạn.</li>
                <li><strong>Cơ quan nhà nước có thẩm quyền:</strong> khi pháp luật yêu cầu.</li>
            </ul>

            <h2>4. Lưu trữ và chuyển dữ liệu ra nước ngoài</h2>
            <p>Dữ liệu được lưu trên hạ tầng đám mây của các nhà cung cấp quốc tế, máy chủ cơ sở dữ liệu đặt tại Singapore. Chúng tôi lựa chọn nhà cung cấp có cam kết bảo mật và chỉ chuyển dữ liệu cần thiết cho việc vận hành dịch vụ.</p>

            <h2>5. Thời gian lưu trữ</h2>
            <ul>
                <li>Thông tin tài khoản được lưu trong thời gian tài khoản còn hoạt động.</li>
                <li>Lịch sử giao dịch số dư, đơn hàng và rút tiền được lưu tối thiểu 10 năm theo quy định về kế toán, kể cả khi tài khoản đã xoá.</li>
                <li>Nhật ký truy cập và dữ liệu chống gian lận được lưu tối đa 2 năm.</li>
                <li>Khi bạn yêu cầu xoá tài khoản, chúng tôi xoá hoặc ẩn danh hoá dữ liệu cá nhân trong vòng 30 ngày, trừ dữ liệu phải giữ theo quy định pháp luật.</li>
            </ul>

            <h2>6. Bảo mật dữ liệu</h2>
            <ul>
                <li>Kết nối tới website được mã hoá bằng HTTPS.</li>
                <li>Mật khẩu được băm một chiều, khoá tích hợp và dữ liệu nhạy cảm được mã hoá khi lưu.</li>
                <li>Chỉ nhân sự có nhiệm vụ mới được truy cập dữ liệu, mọi thay đổi số dư đều được ghi nhật ký và không thể sửa xoá.</li>
                <li>Tài khoản ngân hàng mới thêm có thời gian chờ trước khi nhận tiền rút để giảm rủi ro khi tài khoản bị chiếm.</li>
            </ul>
            <p>Không có hệ thống nào an toàn tuyệt đối. Nếu xảy ra sự cố lộ lọt dữ liệu ảnh hưởng tới bạn, chúng tôi sẽ thông báo cho bạn và cơ quan có thẩm quyền theo quy định.</p>

            <h2>7. Cookie và lưu trữ trên trình duyệt</h2>
            <ul>
                <li><strong>Phiên đăng nhập và bảo mật biểu mẫu:</strong> bắt buộc để website hoạt động.</li>
                <li><strong>Mã định danh thiết bị:</strong> dùng để phòng chống tạo nhiều tài khoản, lưu tối đa 5 năm.</li>
                <li><strong>Mã giới thiệu:</strong> ghi nhớ người đã mời bạn trong 30 ngày.</li>
                <li><strong>Giao diện sáng/tối:</strong> lưu lựa chọn của bạn ngay trên trình duyệt.</li>
                <li><strong>Google Analytics:</strong> cookie thống kê truy cập.</li>
            </ul>
            <p>Bạn có thể xoá hoặc chặn cookie trong cài đặt trình duyệt, nhưng một số tính năng như đăng nhập có thể không hoạt động.</p>

            <h2>8. Quyền của bạn</h2>
            <p>Theo quy định pháp luật, bạn có quyền:</p>
            <ul>
                <li>Được biết về việc xử lý dữ liệu và xem dữ liệu cá nhân của mình.</li>
                <li>Chỉnh sửa thông tin trong trang Hồ sơ hoặc yêu cầu chúng tôi chỉnh sửa.</li>
                <li>Yêu cầu xoá tài khoản và dữ liệu cá nhân, rút lại sự đồng ý hoặc hạn chế xử lý dữ liệu.</li>
                <li>Huỷ liên kết Telegram bất cứ lúc nào trong trang Hồ sơ hoặc bằng lệnh /huylienket trong bot; khi đó chúng tôi xoá chat ID và tên người dùng Telegram khỏi tài khoản của bạn.</li>
                <li>Phản đối việc xử lý dữ liệu, khiếu nại và yêu cầu bồi thường theo quy định.</li>
            </ul>
            <p>Gửi yêu cầu qua mục <a href="{{ route('dashboard.tickets.create') }}">hỗ trợ</a>. Chúng tôi phản hồi trong vòng 72 giờ và có thể cần xác minh danh tính trước khi xử lý. Số dư khả dụng còn lại nên được rút trước khi xoá tài khoản.</p>

            <h2>9. Trẻ em</h2>
            <p>Dịch vụ không dành cho người dưới 16 tuổi. Nếu biết đã thu thập dữ liệu của trẻ em khi chưa có sự đồng ý của cha mẹ/người giám hộ, chúng tôi sẽ xoá dữ liệu đó.</p>

            <h2>10. Thay đổi chính sách</h2>
            <p>Chúng tôi có thể cập nhật chính sách này và sẽ ghi rõ ngày cập nhật ở đầu trang. Thay đổi quan trọng sẽ được thông báo trên website hoặc trong tài khoản của bạn.</p>

            <h2>11. Liên hệ</h2>
            <ul>
                <li>Gửi yêu cầu hỗ trợ: <a href="{{ route('dashboard.tickets.create') }}">trong tài khoản của bạn</a></li>
                @if ($email)
                    <li>Email: <a href="mailto:{{ $email }}">{{ $email }}</a></li>
                @endif
                @if ($address)
                    <li>Địa chỉ: {{ $address }}</li>
                @endif
                @if ($hotline)
                    <li>Hotline: <a href="tel:{{ $hotline }}">{{ $hotline }}</a></li>
                @endif
                @if ($zalo)
                    <li>Zalo: <a href="{{ $zalo }}" target="_blank" rel="noopener">Zalo hỗ trợ</a></li>
                @endif
            </ul>
        </div>
    </div>
</x-layouts.app>
