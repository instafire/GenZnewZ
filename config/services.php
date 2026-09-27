<?php

return [

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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'coinmarketcap' => [
        'api_key' => env('COINMARKETCAP_API_KEY'),
    ],

    'massive' => [
        'api_key' => env('MASSIVE_API_KEY'),
    ],

    'pexels' => [
        'api_key' => env('PEXELS_API_KEY'),
    ],

    'anthropic' => [
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.minimax.io/anthropic'),
        'auth_token' => env('ANTHROPIC_AUTH_TOKEN'),
        'model' => env('ANTHROPIC_MODEL', env('ANTHROPIC_DEFAULT_SONNET_MODEL', 'MiniMax-M2.5')),
        'api_timeout_ms' => (int) env('API_TIMEOUT_MS', 45000),
    ],

];
