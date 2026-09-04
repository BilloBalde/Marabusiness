<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000', // React Native dev
        'http://localhost:8081', // Expo
        'http://localhost:19006', // Expo web
        'http://localhost:19002', // Expo dev tools
        'http://10.0.2.2:8000', // Android emulator
        'http://localhost:*',  // Allow any port on localhost
        'http://127.0.0.1:*',  // Allow any port on 127.0.0.1
        '*',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];