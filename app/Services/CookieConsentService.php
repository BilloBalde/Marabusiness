<?php

namespace App\Services;

use Illuminate\Support\Facades\Cookie;

class CookieConsentService
{
    /**
     * Default cookie settings
     */
    protected array $defaultSettings = [
        'essential' => true,  // Always true, cannot be disabled
        'functional' => false,
        'analytics' => false,
        'marketing' => false,
    ];

    /**
     * Cookie names used in the application
     */
    const CONSENT_COOKIE_NAME = 'cookie_consent';
    const SETTINGS_COOKIE_NAME = 'cookie_settings';

    /**
     * Get user's cookie consent preferences
     */
    public function getConsentSettings(): array
    {
        if (Cookie::has(self::SETTINGS_COOKIE_NAME)) {
            $settings = json_decode(Cookie::get(self::SETTINGS_COOKIE_NAME), true);
            return array_merge($this->defaultSettings, $settings);
        }

        return $this->defaultSettings;
    }

    /**
     * Check if a specific cookie category is allowed
     */
    public function isCategoryAllowed(string $category): bool
    {
        $settings = $this->getConsentSettings();
        return $settings[$category] ?? false;
    }

    /**
     * Save cookie consent preferences
     */
    public function saveConsent(array $preferences, int $lifetime = 365 * 24 * 60): void
    {
        // Ensure essential cookies are always enabled
        $preferences['essential'] = true;

        // Save the consent cookie (remembers that user made a choice)
        Cookie::queue(self::CONSENT_COOKIE_NAME, true, $lifetime);

        // Save the actual settings
        Cookie::queue(self::SETTINGS_COOKIE_NAME, json_encode($preferences), $lifetime);

        // Log the consent if needed (optional)
        if (config('cookie-consent.log_consent', false)) {
            $this->logConsent($preferences);
        }
    }

    /**
     * Check if user has already given consent
     */
    public function hasConsent(): bool
    {
        return Cookie::has(self::CONSENT_COOKIE_NAME);
    }

    /**
     * Withdraw consent (delete cookies)
     */
    public function withdrawConsent(): void
    {
        Cookie::queue(Cookie::forget(self::CONSENT_COOKIE_NAME));
        Cookie::queue(Cookie::forget(self::SETTINGS_COOKIE_NAME));
    }

    /**
     * Get cookie banner display status
     */
    public function shouldShowBanner(): bool
    {
        return !$this->hasConsent();
    }

    /**
     * Log consent for compliance (GDPR)
     */
    protected function logConsent(array $preferences): void
    {
        if (config('cookie-consent.log_consent', false)) {
            \Log::info('Cookie consent given', [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'preferences' => $preferences,
                'timestamp' => now(),
            ]);
        }
    }

    /**
     * Get all available cookie categories with descriptions
     */
    public static function getCategories(): array
    {
        return [
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
            'functional' => [
                'title' => 'Fonctionnels',
                'description' => 'Améliorent votre expérience en mémorisant vos préférences.',
                'always_active' => false,
                'cookies' => [
                    ['name' => 'language', 'purpose' => 'Langue préférée', 'duration' => '6 mois'],
                    ['name' => 'currency', 'purpose' => 'Devise préférée', 'duration' => '6 mois'],
                    // Un cookie « cart_items » de 30 jours était annoncé ici : le
                    // site ne le pose plus. La version qui l'écrivait est commentée
                    // (CartManagement::addCartItemsToCookie, lignes 87-92) et le
                    // panier est enregistré en base, sur le compte du client
                    // (table cart_items), sans échéance. Annoncer un cookie qui
                    // n'existe pas, et taire le stockage qui existe, c'est un
                    // avis de consentement faux dans les deux sens.
                ]
            ],
            'analytics' => [
                'title' => 'Analytiques',
                'description' => 'Nous aident à comprendre comment les visiteurs utilisent notre site.',
                'always_active' => false,
                'cookies' => [
                    ['name' => '_ga', 'purpose' => 'Google Analytics', 'duration' => '2 ans'],
                    ['name' => '_gid', 'purpose' => 'Google Analytics', 'duration' => '24 heures'],
                    ['name' => '_gat', 'purpose' => 'Google Analytics', 'duration' => '1 minute'],
                ]
            ],
            'marketing' => [
                'title' => 'Publicitaires',
                'description' => 'Utilisés pour vous proposer des publicités pertinentes.',
                'always_active' => false,
                'cookies' => [
                    ['name' => '_fbp', 'purpose' => 'Facebook Pixel', 'duration' => '3 mois'],
                    ['name' => 'ads_prefs', 'purpose' => 'Préférences publicitaires', 'duration' => '6 mois'],
                ]
            ],
        ];
    }
}