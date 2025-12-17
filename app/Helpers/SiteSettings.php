<?php

namespace App\Helpers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettings
{
    protected static $settings = null;

    /**
     * Get all settings with caching
     */
    public static function all()
    {
        if (is_null(self::$settings)) {
            self::$settings = Cache::remember('site_settings', 3600, function () {
                return SiteSetting::all()->pluck('value', 'key')->toArray();
            });
        }

        return self::$settings;
    }

    /**
     * Get a specific setting
     */
    public static function get($key, $default = null)
    {
        $settings = self::all();
        return $settings[$key] ?? $default;
    }

    /**
     * Get banner image URL
     */
    // In app/Helpers/SiteSettings.php
    public static function banner($key)
    {
        $value = self::get($key);
        
        if ($value) {
            // Check if it's a full URL already
            if (str_starts_with($value, 'http')) {
                return $value;
            }
            
            // Check if file exists
            $filePath = public_path('uploads/' . $value);
            if (file_exists($filePath)) {
                return asset('uploads/' . $value);
            }
        }
        
        // Return defaults
        return match($key) {
            'homepage_big_banner' => asset('assets/images/big banner.png'),
            'promo_banner_1' => asset('assets/images/promo1.jpg'),
            'promo_banner_2' => asset('assets/images/promo2.jpg'),
            'sidebar_banner' => asset('assets/images/sidebar-banner.jpg'),
            'site_logo' => asset('assets/images/logo.png'),
            default => null,
        };
    }

    /**
     * Get logo URL
     */
    public static function logo()
    {
        return self::banner('site_logo');
    }

    /**
     * Clear cache (call this when settings are updated)
     */
    public static function clearCache()
    {
        Cache::forget('site_settings');
        self::$settings = null;
    }

    /**
     * Get contact information
     */
    public static function contact()
    {
        return [
            'email' => self::get('contact_email'),
            'phone' => self::get('contact_phone'),
            'address' => self::get('contact_address'),
        ];
    }

    /**
     * Get social media links
     */
    public static function social()
    {
        return [
            'facebook' => self::get('facebook_url'),
            'twitter' => self::get('twitter_url'),
            'instagram' => self::get('instagram_url'),
            'linkedin' => self::get('linkedin_url'),
        ];
    }

    /**
     * Get SEO settings
     */
    public static function seo()
    {
        return [
            'title' => self::get('meta_title'),
            'description' => self::get('meta_description'),
            'keywords' => self::get('meta_keywords'),
        ];
    }

    /**
     * Get boolean setting
     */
    public static function isEnabled($key)
    {
        $value = self::get($key);
        return $value === '1' || $value === 'true' || $value === true;
    }
}