@php
    $siteName = setting('site_name');
    $hotline = setting('support_hotline');
    $zalo = setting('support_zalo');
    $email = setting('support_email');
    $operator = setting('operator_name') ?: 'một cá nhân';
    $address = setting('operator_address');
    $holdHours = (int) setting('withdraw_new_bank_hold_hours');
    $withdrawMax = (int) setting('withdraw_max');
    $withdrawFee = (int) setting('withdraw_fee');
@endphp

<x-layouts.app title="Điều khoản sử dụng" description="Điều khoản sử dụng dịch vụ hoàn tiền {{ $siteName }}: tài khoản, cách tính hoàn tiền, rút tiền, hành vi bị cấm và giải quyết tranh chấp.">
    <div class="container-page max-w-3xl py-10">
        <h1 class="text-3xl font-bold text-stone-900">Điều khoản sử dụng</h1>
        <p class="mt-2 text-sm text-stone-500">Cập nhật lần cuối: 27/10/2026</p>

        <div class="prose-content mt-6">
            <p>Chào mừng bạn đến với {{ $siteName }} ("chúng tôi"), website do {{ $operator }} sở hữu và vận hành{{ $address ? ', địa chỉ '.$address : '' }}. Bằng việc đăng ký tài khoản hoặc sử dụng website, bạn đồng ý với các điều khoản dưới đây. Vui lòng đọc kỹ cùng với <a href="{{ route('pages.privacy') }}">Chính sách bảo mật</a>.</p>

            <h2>1. Dịch vụ của chúng tôi</h2>
            <p>{{ $siteName }} là đối tác tiếp thị liên kết của các sàn thương mại điện tử và thương hiệu (Shopee, TikTok Shop, Lazada...). Khi bạn mua hàng qua link hoàn tiền do chúng tôi tạo, sàn trả cho chúng tôi một khoản hoa hồng và chúng tôi chia lại phần lớn khoản đó cho bạn dưới dạng tiền hoàn.</p>
            <ul>
                <li>Chúng tôi không bán hàng, không giao hàng và không phải bên bán. Mọi vấn đề về sản phẩm, giao hàng, đổi trả, bảo hành do sàn và người bán chịu trách nhiệm.</li>
                <li>Giá sản phẩm và quyền lợi của bạn trên sàn không thay đổi khi mua qua link hoàn tiền.</li>
                <li>Sử dụng dịch vụ là miễn phí.</li>
            </ul>

            <h2>2. Tài khoản</h2>
            <ul>
                <li>Bạn phải từ đủ 18 tuổi, hoặc có sự đồng ý của cha mẹ/người giám hộ, để đăng ký và rút tiền.</li>
                <li>Mỗi người chỉ được sở hữu <strong>một tài khoản</strong>. Thông tin đăng ký phải chính xác và là của bạn.</li>
                <li>Tài khoản đăng ký bằng email cần xác thực email trước khi sử dụng. Tài khoản đăng nhập bằng Google được xác thực qua Google.</li>
                <li>Bạn chịu trách nhiệm giữ bí mật mật khẩu và mọi hoạt động diễn ra trên tài khoản của mình. Hãy báo ngay cho chúng tôi nếu nghi ngờ tài khoản bị truy cập trái phép.</li>
            </ul>

            <h2>3. Cách tiền hoàn được ghi nhận</h2>
            <ol>
                <li><strong>Ghi nhận:</strong> đơn hàng mua qua link hoàn tiền thường xuất hiện trong tài khoản sau vài phút tới vài giờ, tuỳ tốc độ báo cáo của sàn. Số tiền hoàn lúc này là <em>dự kiến</em>.</li>
                <li><strong>Chờ duyệt:</strong> sàn cần thời gian xác nhận đơn không bị huỷ, hoàn hàng hay gian lận, thường 30–60 ngày kể từ ngày mua tuỳ sàn.</li>
                <li><strong>Khả dụng:</strong> khi sàn chốt hoa hồng, tiền hoàn chuyển vào số dư khả dụng và bạn có thể rút.</li>
            </ol>
            <p>Số tiền hoàn thực tế phụ thuộc vào hoa hồng sàn trả cho từng đơn và có thể khác số dự kiến do ngành hàng, mã giảm giá, phí vận chuyển hoặc chính sách của sàn tại thời điểm mua.</p>
            <p>Chúng tôi không đảm bảo mọi đơn hàng đều được sàn ghi nhận. Đơn có thể không được ghi nhận nếu bạn mở link khác (link tiếp thị khác, trang so sánh giá, tiện ích trình duyệt) sau khi bấm link hoàn tiền, dùng trình chặn quảng cáo, hoặc không hoàn tất đơn trong phiên mua sắm. Nếu thiếu đơn, hãy <a href="{{ route('dashboard.tickets.create') }}">gửi yêu cầu hỗ trợ</a> kèm mã đơn hàng, chúng tôi sẽ đối soát với sàn nhưng kết quả cuối cùng phụ thuộc vào dữ liệu sàn cung cấp.</p>

            <h2>4. Huỷ đơn và thu hồi tiền hoàn</h2>
            <p>Đơn bị huỷ, hoàn hàng, trả hàng hoặc bị sàn từ chối hoa hồng sẽ không được hoàn tiền. Nếu sàn thu hồi hoa hồng sau khi tiền hoàn đã vào số dư khả dụng, chúng tôi sẽ trừ lại khoản tương ứng trên số dư của bạn. Mọi biến động số dư đều được ghi lại trong mục Biến động số dư.</p>

            <h2>5. Rút tiền</h2>
            <ul>
                <li>Số tiền rút tối thiểu là <strong>{{ vnd((int) setting('withdraw_min')) }}</strong>{{ $withdrawMax > 0 ? ', tối đa '.vnd($withdrawMax).' mỗi lần' : '' }}. Phí rút: <strong>{{ $withdrawFee > 0 ? vnd($withdrawFee).' mỗi lần' : 'miễn phí' }}</strong>.</li>
                <li>Tài khoản nhận tiền phải <strong>đứng tên chính bạn</strong>. Chúng tôi có thể từ chối chi trả về tài khoản không trùng tên chủ tài khoản thành viên.</li>
                @if ($holdHours > 0)
                    <li>Để phòng trường hợp tài khoản bị chiếm và đổi số tài khoản nhận tiền, tài khoản ngân hàng mới thêm chỉ nhận được tiền rút sau <strong>{{ $holdHours }} giờ</strong>.</li>
                @endif
                <li>Yêu cầu rút được xử lý trong 1–3 ngày làm việc. Khi tạo yêu cầu, số tiền được tạm giữ; nếu yêu cầu bị từ chối hoặc bạn tự huỷ, tiền trở lại số dư khả dụng.</li>
                <li>Chúng tôi có thể yêu cầu xác minh danh tính, tạm hoãn hoặc từ chối yêu cầu rút có dấu hiệu bất thường cho tới khi xác minh xong.</li>
                <li>Bạn chịu trách nhiệm về tính chính xác của thông tin tài khoản nhận tiền. Chúng tôi không chịu trách nhiệm với khoản chuyển đúng theo thông tin bạn cung cấp.</li>
            </ul>

            <h2>6. Giới thiệu bạn bè và chương trình thưởng</h2>
            <ul>
                <li>Bạn nhận thêm hoa hồng giới thiệu trên đơn hàng hợp lệ của người bạn mời (F1) và người họ mời (F2) theo tỷ lệ công bố tại trang <a href="{{ route('rates') }}">Tỷ lệ hoàn tiền</a>.</li>
                <li>Thưởng điểm danh, thưởng cấp độ thành viên và các chương trình khuyến mãi khác có thể được điều chỉnh hoặc kết thúc theo thông báo trên website.</li>
                <li>Tự giới thiệu chính mình hoặc tạo tài khoản ảo để nhận thưởng là vi phạm và sẽ bị thu hồi toàn bộ thưởng liên quan.</li>
            </ul>

            <h2>7. Hành vi bị cấm</h2>
            <ul>
                <li>Tạo hoặc sử dụng nhiều tài khoản, dùng chung tài khoản ngân hàng giữa nhiều tài khoản thành viên.</li>
                <li>Đặt đơn ảo, đặt rồi huỷ hàng loạt, mua để trả hàng, hoặc mọi hình thức gian lận hoa hồng.</li>
                <li>Dùng phần mềm, bot hoặc công cụ tự động để tạo link, đặt đơn, điểm danh hay truy cập website.</li>
                <li>Phát tán link hoàn tiền bằng thư rác, quảng cáo sai sự thật hoặc mạo danh {{ $siteName }} và các sàn.</li>
                <li>Can thiệp, dò quét lỗ hổng hoặc làm gián đoạn hệ thống.</li>
            </ul>

            <h2>8. Xử lý vi phạm</h2>
            <p>Khi phát hiện vi phạm hoặc dấu hiệu gian lận, chúng tôi có quyền tạm khoá tài khoản, tạm dừng rút tiền, thu hồi số dư và tiền hoàn có được từ hành vi vi phạm, từ chối các yêu cầu rút tiền liên quan và khoá vĩnh viễn tài khoản. Bạn có thể khiếu nại qua mục hỗ trợ trong vòng 30 ngày kể từ khi bị xử lý.</p>

            <h2>9. Giới hạn trách nhiệm</h2>
            <ul>
                <li>Chúng tôi cố gắng duy trì website hoạt động ổn định nhưng không đảm bảo dịch vụ luôn liên tục, không lỗi. Website có thể tạm ngừng để bảo trì.</li>
                <li>Chúng tôi không chịu trách nhiệm khi sàn thay đổi chính sách hoa hồng, ngừng hợp tác, chậm hoặc không cung cấp dữ liệu đơn hàng. Trong các trường hợp đó, chúng tôi sẽ thông báo và xử lý số dư của bạn một cách công bằng.</li>
                <li>Trách nhiệm của chúng tôi với bạn trong mọi trường hợp không vượt quá số tiền hoàn đã được ghi nhận hợp lệ trong tài khoản của bạn.</li>
            </ul>

            <h2>10. Thuế</h2>
            <p>Bạn tự chịu trách nhiệm kê khai thuế đối với khoản thu nhập của mình (nếu có) theo quy định pháp luật. Trường hợp pháp luật yêu cầu, chúng tôi sẽ khấu trừ thuế trước khi chi trả và thông báo cho bạn.</p>

            <h2>11. Thay đổi điều khoản</h2>
            <p>Chúng tôi có thể cập nhật điều khoản này. Thay đổi quan trọng sẽ được thông báo trên website hoặc trong tài khoản của bạn ít nhất 7 ngày trước khi có hiệu lực. Tiếp tục sử dụng dịch vụ sau thời điểm đó đồng nghĩa với việc bạn chấp nhận điều khoản mới.</p>

            <h2>12. Luật áp dụng và giải quyết tranh chấp</h2>
            <p>Điều khoản này được điều chỉnh theo pháp luật Việt Nam. Tranh chấp phát sinh sẽ được ưu tiên giải quyết bằng thương lượng; nếu không đạt được thoả thuận, một trong hai bên có quyền đưa ra Toà án có thẩm quyền tại Việt Nam.</p>

            <h2>13. Liên hệ</h2>
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
