<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression test for the leftover debug/test routes removed from routes/web.php.
 * The two worst offenders, /test-add-direct and /test-add-simple, wrote a cart line
 * with an attacker-chosen base_price/unit_amount/total_amount straight into the
 * session — and CheckoutController trusts those cart amounts verbatim when building
 * the order total and the Stripe charge, so anyone who found either route could check
 * out at whatever price they picked. None of them were behind an environment gate, so
 * they were reachable in production exactly like any real route.
 *
 * This test exists purely to catch the routes coming back — by re-introducing one
 * during a merge, a copy-paste, or "just for local debugging" — before it ships.
 */
class DebugRoutesRemovedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array<int, string>>
     */
    public static function removedRoutes(): array
    {
        return [
            ['/debug-routes'],
            ['/debug-bulk-rfq-route'],
            ['/debug-cookie'],
            ['/test-add-direct/1'],
            ['/test-add-direct/1/2'],
            ['/test-cookie-limit'],
            ['/test-419'],
            ['/test-403'],
            ['/test-500'],
            ['/test-add-simple'],
        ];
    }

    #[Test]
    #[DataProvider('removedRoutes')]
    public function the_route_no_longer_exists(string $uri): void
    {
        $this->get($uri)->assertNotFound();
    }

    #[Test]
    public function the_price_tampering_route_specifically_cannot_write_a_custom_priced_item_to_the_cart(): void
    {
        // Belt and suspenders: even if a route with this shape ever came back, this
        // pins down the actual harm it caused — an attacker-chosen price landing in
        // the session cart — as the thing to watch for, not just the URL's status code.
        $this->get('/test-add-direct/1')->assertNotFound();

        $this->assertEmpty(
            session('cart_items', []),
            'A request to the removed price-tampering route must never populate the cart.'
        );
    }
}
