<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cookie Consent Lifetime
    |--------------------------------------------------------------------------
    |
    | This determines how long (in minutes) the cookie consent preferences
    | will be stored. Default is 1 year (525600 minutes)
    |
    */
    'lifetime' => 525600, // 1 year in minutes

    /*
    |--------------------------------------------------------------------------
    | Log Consent
    |--------------------------------------------------------------------------
    |
    | When enabled, each consent action will be logged for compliance purposes
    |
    */
    'log_consent' => env('COOKIE_CONSENT_LOG', false),

    /*
    |--------------------------------------------------------------------------
    | Enable Analytics by Default
    |--------------------------------------------------------------------------
    |
    | You can set default values for cookie categories here
    |
    */
    'defaults' => [
        'essential' => true,
        'functional' => false,
        'analytics' => false,
        'marketing' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cookie Categories
    |--------------------------------------------------------------------------
    |
    | Define your cookie categories and their cookies
    |
    */
    'categories' => [
        'essential' => [
            'title' => 'Essentiels',
            'description' => 'Nécessaires au fonctionnement du site, ne peuvent être désactivés.',
            'always_active' => true,
            'cookies' => [
                ['name' => 'laravel_session', 'purpose' => 'Session utilisateur', 'duration' => '2 heures'],
                ['name' => 'XSRF-TOKEN', 'purpose' => 'Sécurité CSRF', 'duration' => '2 heures'],
                ['name' => 'cookie_consent', 'purpose' => 'Préférences de cookies', 'duration' => '1 an'],
            ]
        ],
        // ... other categories
    ],
];