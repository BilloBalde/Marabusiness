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

    'dhl' => [
        'api_key' => env('DHL_API_KEY'),
        'api_secret' => env('DHL_API_SECRET'),
        'account_number' => env('DHL_ACCOUNT_NUMBER'), // Add this
        'environment' => env('DHL_ENVIRONMENT', 'sandbox'), // Add this
        // For tracking API specifically
        'tracking_url' => env('DHL_TRACKING_URL', 'https://api-eu.dhl.com/track/shipments'),
        'base_url' => env('DHL_BASE_URL', 'https://api-eu.dhl.com'),
        // DHL Express specific
        'express_url' => env('DHL_EXPRESS_URL', 'https://api-eu.dhl.com/dhlexpress'),
    ],

    'lengopay' => [
        'base_url'    => env('LENGOPAY_BASE_URL', 'https://portal.lengopay.com'),
        'license_key' => env('LENGOPAY_LICENSE_KEY'),
        'website_id'  => env('LENGOPAY_WEBSITE_ID'),
        'currency'    => env('LENGOPAY_CURRENCY', 'GNF'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        /* 'guzzle' => [
            'proxy' => 'http://127.0.0.1:7890',
            'verify' => false,
            'timeout' => 60,
        ], */
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
        /* 'guzzle' => [
            'proxy' => 'http://127.0.0.1:7890',
            'verify' => false,
            'timeout' => 60,
        ], */
    ],


];
