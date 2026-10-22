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
        'site_name' => env('APP_NAME', 'Sàn Hoàn Tiền'),
        'support_hotline' => '',
        'support_zalo' => '',
        'support_facebook' => '',

        // Rút tiền (VND)
        'withdraw_min' => 50000,
        'withdraw_max' => 20000000,
        'withdraw_fee' => 0,
        'withdraw_max_pending' => 1,

        // Điểm danh: thưởng theo ngày thứ n trong chuỗi, lặp lại sau khi hết mảng
        'checkin_rewards' => '100,100,200,200,300,300,500',

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
