<?php

namespace Tests\Feature\Payment;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * GET /success-stripe with no session_id — someone opening the link directly, or
 * refreshing it after the Stripe redirect session already expired — used to 500
 * instead of showing the "Payment Error" screen it was clearly built to show.
 *
 * SuccessPageStripe::mount() already handled this case correctly: no session_id
 * sets $error and returns early, leaving $orderId null. The crash was purely in
 * the view — the error screen's "Back to Order" link called
 * route('my-orders.show', $orderId) unconditionally, and that route's {order_id}
 * segment is required, so passing null threw UrlGenerationException before the
 * page ever rendered.
 */
class StripeSuccessPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('customer', 'web');
    }

    #[Test]
    public function no_session_id_shows_the_error_screen_instead_of_crashing(): void
    {
        $response = $this->get('/success-stripe');

        $response->assertOk();
        $response->assertSee('Payment Error');
        $response->assertSee('Missing session ID');
    }

    #[Test]
    public function the_error_screen_has_no_back_to_order_link_when_no_order_is_known(): void
    {
        $response = $this->get('/success-stripe');

        $response->assertOk();
        $response->assertDontSee('Back to Order');
        // The fallback is still there — a dead end never leaves the buyer stuck.
        $response->assertSee('View All Orders');
    }

    #[Test]
    public function the_page_works_the_same_way_for_a_signed_in_customer(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $this->actingAs($user);

        $this->get('/success-stripe')->assertOk();
    }
}
