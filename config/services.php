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
    'pagofacil' => [
        'commerce_id' => env('PAGOFACIL_COMMERCE_ID'),
        'service_token' => env('PAGOFACIL_SERVICE_TOKEN'),
        'secret_token' => env('PAGOFACIL_SECRET_TOKEN'),
        'base_url' => env('PAGOFACIL_BASE_URL', 'https://masterqr.pagofacil.com.bo/api/services/v2'),
        'callback_url' => env('URL_CALLBACK'),
        'webhook_secret' => env('PAGOFACIL_WEBHOOK_SECRET'),
        'monto_prueba' => env('PAGOFACIL_MONTO_PRUEBA', 0.01),
        'payment_method' => env('PAGOFACIL_PAYMENT_METHOD', 'auto'),
        'cache_segundos' => env('PAGOFACIL_CACHE_SEGUNDOS', 600),
    ],

];
