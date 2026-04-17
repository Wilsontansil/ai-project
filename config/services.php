<?php

return [

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    'whatsapp' => [
        'base_url' => env('WAHA_BASE_URL'),
        'session' => env('WAHA_SESSION', 'default'),
        'api_key' => env('WAHA_API_KEY'),
        'webhook_secret' => env('WAHA_WEBHOOK_SECRET'),
    ],

    'livechat' => [
        'verify_token' => env('LIVECHAT_VERIFY_TOKEN'),
    ],

    'agent' => [
        'id' => env('AGENT_ID', 1),
        'kode' => env('AGENT_KODE', 'PG'),
    ],

    'support' => [
        'phone' => env('SUPPORT_PHONE', '08120000000'),
        'telegram_url' => env('SUPPORT_TELEGRAM_URL'),
        'whatsapp_url' => env('SUPPORT_WHATSAPP_URL'),
    ],

];
