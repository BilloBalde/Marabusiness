<?php

namespace Tests\Feature\Payment;

use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Card payments were only ever credited when the buyer's browser came back to the
 * success page. A buyer who paid and closed the tab — or whose phone never opened the
 * mobile deep link — was charged and left with an unpaid order. Stripe now tells us
 * itself, signed with the endpoint secret.
 */
class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::SECRET]);
    }

    private function order(array $attributes = []): Order
    {
        $vendor = Vendor::factory()->create([
            'currency_id' => Currency::factory()->create(['code' => 'USD', 'rate_to_usd' => 1])->id,
        ]);

        return Order::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'WH-' . uniqid(),
            'grand_total' => 100,
            'total_paid' => 0,
            'total_remaining' => 100,
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
            'status' => 'new',
            'rate_to_usd' => 1,
        ], $attributes));
    }

    private function event(Order $order, string $type = 'checkout.session.completed', string $status = 'paid', string $sessionId = 'cs_test_wh'): string
    {
        return json_encode([
            'id' => 'evt_' . uniqid(),
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => [
                'id' => $sessionId,
                'object' => 'checkout.session',
                'payment_status' => $status,
                'amount_total' => 10000,
                'currency' => 'usd',
                'metadata' => ['order_id' => (string) $order->id],
            ]],
        ]);
    }

    private function deliver(string $payload, ?string $secret = self::SECRET)
    {
        $headers = ['CONTENT_TYPE' => 'application/json'];

        if ($secret !== null) {
            $timestamp = time();
            $headers['HTTP_STRIPE_SIGNATURE'] = "t={$timestamp},v1=" . hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
        }

        // The signature covers the exact bytes, so the body is sent as-is rather than
        // re-encoded by postJson().
        return $this->call('POST', '/api/payments/stripe/webhook', [], [], [], $headers, $payload);
    }

    #[Test]
    public function a_signed_paid_session_credits_the_order_it_names(): void
    {
        $order = $this->order();

        $this->deliver($this->event($order))->assertOk();

        $this->assertDatabaseHas('paiements', ['order_id' => $order->id, 'transaction_id' => 'stripe_cs_test_wh']);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    #[Test]
    public function stripe_delivering_the_same_event_twice_credits_once(): void
    {
        $order = $this->order();
        $payload = $this->event($order);

        $this->deliver($payload)->assertOk();
        $this->deliver($payload)->assertOk();

        $this->assertSame(1, $order->paiements()->count());
    }

    #[Test]
    public function a_call_with_a_wrong_signature_is_refused_and_records_nothing(): void
    {
        $order = $this->order();

        $this->deliver($this->event($order), secret: 'whsec_someone_else')->assertStatus(400);

        $this->assertDatabaseMissing('paiements', ['order_id' => $order->id]);
    }

    #[Test]
    public function an_unsigned_call_is_refused(): void
    {
        $order = $this->order();

        $this->deliver($this->event($order), secret: null)->assertStatus(400);

        $this->assertDatabaseMissing('paiements', ['order_id' => $order->id]);
    }

    #[Test]
    public function without_a_configured_secret_nothing_is_accepted(): void
    {
        config(['services.stripe.webhook_secret' => null]);
        $order = $this->order();

        $this->deliver($this->event($order))->assertStatus(503);

        $this->assertDatabaseMissing('paiements', ['order_id' => $order->id]);
    }

    #[Test]
    public function other_event_types_are_acknowledged_and_ignored(): void
    {
        $order = $this->order();

        $this->deliver($this->event($order, type: 'charge.refunded'))->assertOk();

        $this->assertDatabaseMissing('paiements', ['order_id' => $order->id]);
    }

    #[Test]
    public function a_session_that_is_not_paid_yet_records_nothing(): void
    {
        $order = $this->order();

        $this->deliver($this->event($order, status: 'unpaid'))->assertOk();

        $this->assertDatabaseMissing('paiements', ['order_id' => $order->id]);
    }

    #[Test]
    public function a_delayed_payment_that_later_succeeds_is_credited(): void
    {
        $order = $this->order();

        $this->deliver($this->event($order, type: 'checkout.session.async_payment_succeeded'))->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    #[Test]
    public function an_order_that_was_cancelled_meanwhile_is_not_credited(): void
    {
        $order = $this->order(['status' => 'cancelled']);

        $this->deliver($this->event($order))->assertOk();

        $this->assertDatabaseMissing('paiements', ['order_id' => $order->id]);
    }
}
