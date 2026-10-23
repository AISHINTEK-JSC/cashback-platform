<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/auth/google/callback',
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('APP_URL').'/auth/facebook/callback',
    ],

    /*
    | AccessTrade: một key cho cả tài khoản publisher, mỗi sàn ứng với một campaign.
    | Ghi đè campaign_id/merchant của từng sàn trong api_config ở trang quản trị Sàn liên kết.
    */
    'accesstrade' => [
        'key' => env('ACCESSTRADE_API_KEY'),
        'endpoint' => env('ACCESSTRADE_ENDPOINT', 'https://api.accesstrade.vn/v1'),
        'campaigns' => [
            'tiktok' => ['campaign_id' => '6648523843406889655', 'merchant' => 'tiktok_cps'],
            'shopee' => ['campaign_id' => '4751584435713464237', 'merchant' => 'shopee'],
            'lazada' => ['campaign_id' => '5249638763776692551', 'merchant' => 'lazada_cashback'],
        ],
    ],

];
