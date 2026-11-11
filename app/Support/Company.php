<?php

namespace App\Support;

use App\Helpers\SiteSettings;

/**
 * The operator's identity and contact details, for the legal pages.
 *
 * Nothing here is invented: a value comes from config/company.php (.env), or — for the
 * phone, e-mail and address — from the contact settings the owner keeps in the admin
 * panel, or it is null and the page leaves the line out.
 */
class Company
{
    public static function get(string $key): ?string
    {
        $value = trim((string) config("company.{$key}"));

        if ($value !== '') {
            return $value;
        }

        $fallback = match ($key) {
            'phone' => SiteSettings::get('contact_phone'),
            'email' => SiteSettings::get('contact_email'),
            'address' => SiteSettings::get('contact_address'),
            default => null,
        };

        $fallback = trim((string) $fallback);

        return $fallback !== '' ? $fallback : null;
    }

    /** The address to write to about personal data, else the general one. */
    public static function dpoEmail(): ?string
    {
        return self::get('dpo_email') ?? self::get('email');
    }

    /** Digits and a leading + only, for a tel: link. */
    public static function digits(string $phone): string
    {
        return preg_replace('/[^\d+]/', '', $phone);
    }
}
