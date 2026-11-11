<?php

namespace Tests\Feature\Content;

use App\Helpers\SiteSettings;
use App\Livewire\ContactPage;
use App\Livewire\Partials\Footer;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The contact page and the footer printed invented details — +123 456 7890,
 * contact@yourcompany.com, "123 Main Street, Dakar", a Trustpilot rating of "4.5/5
 * (2,500+ reviews)" and store buttons pointing at "#" — while the real ones sat in the
 * site settings table, read by nothing. They now show what the owner entered, and
 * leave a block out when its setting is empty.
 */
class ContactAndFooterUseSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('site_settings');
        SiteSettings::clearCache();
    }

    private function setting(string $key, string $value): void
    {
        SiteSetting::create([
            'key' => $key, 'label' => $key, 'group' => 'contact', 'type' => 'text', 'value' => $value,
        ]);
        SiteSettings::clearCache();
    }

    #[Test]
    public function nothing_invented_is_shown_when_no_setting_is_filled(): void
    {
        $contact = Livewire::test(ContactPage::class)->html();
        $footer = Livewire::test(Footer::class)->html();

        foreach (['+123 456 7890', 'yourcompany.com', '123 Main Street'] as $invented) {
            $this->assertStringNotContainsString($invented, $contact);
        }

        foreach (['+221 78 123 45 67', 'Trustpilot', '2,500+', '4.5/5'] as $invented) {
            $this->assertStringNotContainsString($invented, $footer);
        }
    }

    #[Test]
    public function the_contact_page_shows_the_details_the_owner_entered(): void
    {
        $this->setting('contact_phone', '+224 620 00 00 00');
        $this->setting('contact_email', 'bonjour@exemple.test');
        $this->setting('contact_address', "Kaloum, Conakry\nGuinée");
        $this->setting('business_hours', "Lundi - Vendredi : 9h - 18h\nSamedi : 9h - 13h");
        $this->setting('facebook_url', 'https://facebook.com/maraboutique');

        Livewire::test(ContactPage::class)
            ->assertSee('+224 620 00 00 00')
            ->assertSee('bonjour@exemple.test')
            ->assertSee('Kaloum, Conakry')
            ->assertSee('Lundi - Vendredi : 9h - 18h')
            ->assertSee('https://facebook.com/maraboutique', escape: false);
    }

    #[Test]
    public function the_footer_shows_the_phone_and_store_links_only_when_they_are_set(): void
    {
        $this->setting('contact_phone', '+224 620 00 00 00');
        $this->setting('play_store_url', 'https://play.google.com/store/apps/details?id=test');

        Livewire::test(Footer::class)
            ->assertSee('+224 620 00 00 00')
            ->assertSee('Google Play')
            ->assertDontSee('App Store');
    }

    #[Test]
    public function a_footer_with_no_store_urls_offers_no_store_buttons(): void
    {
        Livewire::test(Footer::class)
            ->assertDontSee('Google Play')
            ->assertDontSee('App Store');
    }
}
