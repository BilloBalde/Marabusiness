<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Banner Settings
            [
                'key' => 'homepage_big_banner',
                'label' => 'Homepage Big Banner',
                'type' => 'image',
                'group' => 'banners',
                'description' => 'The main banner image displayed on the homepage',
                'value' => null,
            ],
            [
                'key' => 'homepage_banner_link',
                'label' => 'Banner Link',
                'type' => 'url',
                'group' => 'banners',
                'description' => 'URL to redirect when clicking the homepage banner',
                'value' => '#',
            ],
            [
                'key' => 'promo_banner_1',
                'label' => 'Promo Banner 1',
                'type' => 'image',
                'group' => 'banners',
                'description' => 'First promotional banner',
                'value' => null,
            ],
            [
                'key' => 'promo_banner_2',
                'label' => 'Promo Banner 2',
                'type' => 'image',
                'group' => 'banners',
                'description' => 'Second promotional banner',
                'value' => null,
            ],
            [
                'key' => 'sidebar_banner',
                'label' => 'Sidebar Banner',
                'type' => 'image',
                'group' => 'banners',
                'description' => 'Banner displayed in the sidebar',
                'value' => null,
            ],

            // General Settings
            [
                'key' => 'site_name',
                'label' => 'Site Name',
                'type' => 'text',
                'group' => 'general',
                'description' => 'The name of your website',
                'value' => 'MARA BUSINESS',
            ],
            [
                'key' => 'site_description',
                'label' => 'Site Description',
                'type' => 'textarea',
                'group' => 'general',
                'description' => 'Brief description of your website',
                'value' => 'Premium business marketplace',
            ],
            [
                'key' => 'site_logo',
                'label' => 'Site Logo',
                'type' => 'image',
                'group' => 'general',
                'description' => 'Main logo of the website',
                'value' => null,
            ],
            [
                'key' => 'favicon',
                'label' => 'Favicon',
                'type' => 'image',
                'group' => 'general',
                'description' => 'Website favicon',
                'value' => null,
            ],

            // Contact Information
            [
                'key' => 'contact_email',
                'label' => 'Contact Email',
                'type' => 'email',
                'group' => 'contact',
                'description' => 'Primary contact email address',
                'value' => 'contact@marabusiness.com',
            ],
            [
                'key' => 'contact_phone',
                'label' => 'Contact Phone',
                'type' => 'text',
                'group' => 'contact',
                'description' => 'Primary contact phone number',
                'value' => '+1 234 567 8900',
            ],
            [
                'key' => 'contact_address',
                'label' => 'Contact Address',
                'type' => 'textarea',
                'group' => 'contact',
                'description' => 'Physical business address',
                'value' => '123 Business Street, City, Country',
            ],

            // Social Media
            [
                'key' => 'facebook_url',
                'label' => 'Facebook URL',
                'type' => 'url',
                'group' => 'social',
                'description' => 'Facebook page/profile URL',
                'value' => 'https://facebook.com/marabusiness',
            ],
            [
                'key' => 'twitter_url',
                'label' => 'Twitter URL',
                'type' => 'url',
                'group' => 'social',
                'description' => 'Twitter profile URL',
                'value' => 'https://twitter.com/marabusiness',
            ],
            [
                'key' => 'instagram_url',
                'label' => 'Instagram URL',
                'type' => 'url',
                'group' => 'social',
                'description' => 'Instagram profile URL',
                'value' => 'https://instagram.com/marabusiness',
            ],
            [
                'key' => 'linkedin_url',
                'label' => 'LinkedIn URL',
                'type' => 'url',
                'group' => 'social',
                'description' => 'LinkedIn company page URL',
                'value' => 'https://linkedin.com/company/marabusiness',
            ],

            // SEO Settings
            [
                'key' => 'meta_title',
                'label' => 'Meta Title',
                'type' => 'text',
                'group' => 'seo',
                'description' => 'Default meta title for pages',
                'value' => 'MARA BUSINESS - Premium Marketplace',
            ],
            [
                'key' => 'meta_description',
                'label' => 'Meta Description',
                'type' => 'textarea',
                'group' => 'seo',
                'description' => 'Default meta description for pages',
                'value' => 'Discover premium products and services on MARA BUSINESS marketplace',
            ],
            [
                'key' => 'meta_keywords',
                'label' => 'Meta Keywords',
                'type' => 'textarea',
                'group' => 'seo',
                'description' => 'Default meta keywords for SEO',
                'value' => 'business, marketplace, products, services, premium',
            ],

            // Appearance
            [
                'key' => 'primary_color',
                'label' => 'Primary Color',
                'type' => 'color',
                'group' => 'appearance',
                'description' => 'Primary brand color',
                'value' => '#D4AF37',
            ],
            [
                'key' => 'secondary_color',
                'label' => 'Secondary Color',
                'type' => 'color',
                'group' => 'appearance',
                'description' => 'Secondary brand color',
                'value' => '#c9a12f',
            ],
            [
                'key' => 'enable_dark_mode',
                'label' => 'Enable Dark Mode',
                'type' => 'boolean',
                'group' => 'appearance',
                'description' => 'Allow users to switch to dark mode',
                'value' => true,
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('✅ Site settings seeded successfully!');
    }
}