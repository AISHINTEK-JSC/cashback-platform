<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cấu hình vận hành mặc định
    |--------------------------------------------------------------------------
    |
    | Giá trị mặc định cho bảng `settings`. Admin có thể ghi đè trong trang
    | Cài đặt của Filament; khi chưa có bản ghi, hệ thống dùng giá trị dưới đây.
    | Kiểu của giá trị mặc định quyết định cách ép kiểu khi đọc từ DB.
    |
    */

    'settings' => [
        'site_name' => 'Ví Hoàn Tiền',
        'support_hotline' => '',
        'support_zalo' => '',
        'support_facebook' => '',
        'support_email' => 'levanthanh.dev@gmail.com',
        // Bên vận hành nêu trong Điều khoản/Chính sách bảo mật (Nghị định 13/2023 yêu cầu nêu rõ bên xử lý dữ liệu)
        'operator_name' => 'Lê Văn Thành',
        'operator_address' => 'Tây Mỗ, Hà Nội',

        // SEO & thống kê: GA4 (G-XXXX) chỉ chạy ở production, mã xác minh là phần content của thẻ meta Search Console
        'google_analytics_id' => env('GOOGLE_ANALYTICS_ID', ''),
        'google_site_verification' => env('GOOGLE_SITE_VERIFICATION', ''),

        // Rút tiền (VND)
        'withdraw_min' => 50000,
        'withdraw_max' => 20000000,
        'withdraw_fee' => 0,
        'withdraw_max_pending' => 1,
        // Tài khoản nhận tiền mới thêm phải chờ ngần này giờ mới nhận được tiền rút (chống chiếm tài khoản rồi đổi STK), 0 = tắt
        'withdraw_new_bank_hold_hours' => 24,

        // Điểm danh: thưởng theo ngày thứ n trong chuỗi, lặp lại sau khi hết mảng
        'checkin_rewards' => '100,100,200,200,300,300,500',

        // Thưởng thành viên mới (VND, 0 = tắt): đơn đầu tiên được duyệt có giá trị từ mức tối thiểu, hoàn thành danh sách hướng dẫn
        'first_order_bonus' => 10000,
        'first_order_bonus_min_order' => 50000,
        'onboarding_bonus' => 5000,

        // Bot Telegram: số tin tối đa mỗi chat được xử lý trong 1 phút (0 = không giới hạn), gợi ý liên kết ở trang tổng quan
        'telegram_chat_rate_limit' => 20,
        'telegram_dashboard_prompt' => true,

        // Chống gian lận
        'fraud_high_cancel_rate' => 60,
        'fraud_high_cancel_min_orders' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Driver affiliate theo sàn
    |--------------------------------------------------------------------------
    |
    | "fake" dùng cho môi trường dev/demo: sinh link giả và đơn giả.
    | Khi có tài khoản affiliate thật, đổi sang driver tương ứng và nhập
    | app_id/secret ở trang quản trị Sàn liên kết.
    |
    */

    'drivers' => [
        'shopee' => env('AFFILIATE_SHOPEE_DRIVER', 'fake'),
        'tiktok' => env('AFFILIATE_TIKTOK_DRIVER', 'fake'),
        'lazada' => env('AFFILIATE_LAZADA_DRIVER', 'fake'),
        'adidas' => env('AFFILIATE_ADIDAS_DRIVER', 'fake'),
        'apple' => env('AFFILIATE_APPLE_DRIVER', 'fake'),
    ],

    /*
    | Số phút lùi lại mỗi lần đồng bộ để không sót đơn ở ranh giới cửa sổ.
    */
    'sync_overlap_minutes' => 30,

    /*
    | Khoảng thời gian (ngày) cron quét lại để cập nhật trạng thái đơn cũ.
    */
    'sync_lookback_days' => 60,

    /*
    | Thời hạn cookie mã giới thiệu (ngày).
    */
    'referral_cookie_days' => 30,

];
