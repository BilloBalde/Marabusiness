<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * /locale/{locale} and /lang/{locale} did the exact same thing — the public
 * navbar used the first, both Filament panels used the second. Consolidated on
 * lang.switch (the navbar's own links now point there); locale.switch is gone.
 */
class LocaleRouteConsolidationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_duplicate_locale_route_no_longer_exists(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('locale.switch'));
    }

    #[Test]
    public function switching_language_through_the_remaining_route_works(): void
    {
        $this->get(route('lang.switch', 'fr'))->assertRedirect();

        $this->assertSame('fr', session('locale'));
    }

    #[Test]
    public function the_homepage_still_renders_with_the_consolidated_route(): void
    {
        $this->get('/')->assertOk();
    }
}
