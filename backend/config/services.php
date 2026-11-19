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
    ],

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    ],

    'ai_service' => [
        'url' => env('AI_SERVICE_URL', 'http://ai-service:8001'),
    ],

    'frontend' => [
        'url' => env('FRONTEND_URL'),
    ],

    'digiflazz' => [
        'username' => env('DIGIFLAZZ_USERNAME'),
        'mode' => env('DIGIFLAZZ_MODE', 'development'),
        'dev_key' => env('DIGIFLAZZ_DEV_KEY'),
        'prod_key' => env('DIGIFLAZZ_PROD_KEY'),
        'webhook_secret' => env('DIGIFLAZZ_WEBHOOK_SECRET'),
    ],

    'ama' => [
        'user_id' => env('AMA_USER_ID', '262711139'),
        'pin' => env('AMA_PIN', '352ee5'),
        'api_url' => env('AMA_API_URL', 'https://202.43.189.122:13008/clientapi/v2/json'),
    ],

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
    ],

    'ppob' => [
        'active_providers' => env('PPOB_ACTIVE_PROVIDERS', 'digiflazz'),
    ],

];
