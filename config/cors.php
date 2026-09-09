<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
    | No '*' here. Combined with supports_credentials below, a wildcard makes the
    | package echo back whatever Origin the caller sends — which let any website on
    | the internet call this API carrying a signed-in customer's cookies and read
    | their orders and addresses. Only real origins belong in this list.
    |
    | The mobile app does not need an entry: it talks to the API natively with an
    | Authorization: Bearer token (see api_service.dart), and native HTTP clients
    | don't perform CORS preflights at all.
    */
    'allowed_origins' => [
        'https://afrobridgeinnov.com',
        'http://localhost:3000',   // React Native dev
        'http://localhost:8081',   // Expo
        'http://localhost:19006',  // Expo web
        'http://localhost:19002',  // Expo dev tools
        'http://10.0.2.2:8000',    // émulateur Android
    ],

    /*
    | Variable dev ports go here, as real regexes. The 'http://localhost:*' entries
    | that used to sit in allowed_origins never matched anything: this package does
    | not expand partial wildcards inside an origin string.
    */
    'allowed_origins_patterns' => [
        '#^http://localhost(:\d+)?$#',
        '#^http://127\.0\.0\.1(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
