<?php

namespace Tests\Feature\Payment;

use App\Livewire\SuccessPage;
use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Services\StripePaymentRecorder;
use App\Support\StripeSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SuccessPage reads Stripe's own record of the session (good), but then chose
 * WHICH order to credit from the ?order_id= in the URL, ahead of the order id
 * Stripe stored in the session's metadata when the checkout was created — and
 * never looked at who was asking or at the order's state. Anyone holding one
 * genuinely paid session could point it at any order id (a stranger's, a
 * cancelled one, one still under negotiation). The session now decides.
 */
class StripeSuccessPageBindingTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(User $buyer, array $attributes = []): Order
    {
        $usd = Currency::where('code', 'USD')->first()
            ?? Currency::factory()->create(['code' => 'USD', 'rate_to_usd' => 1]);
        $vendor = Vendor::factory()->create(['currency_id' => $usd->id]);

        return Order::create(array_merge([
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'STRIPE-' . uniqid(),
            'grand_total' => 100,
            'total_paid' => 0,
            'total_remaining' => 100,
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
            'status' => 'new',
            'rate_to_usd' => 1,
        ], $attributes));
    }

    /** Stripe says: paid, $100.00, created for the given order and user. */
    private function stripeSays(Order $forOrder, ?int $forUserId = null, string $sessionId = 'cs_test_abc', string $status = 'paid'): void
    {
        $session = (object) [
            'id' => $sessionId,
            'payment_status' => $status,
            'amount_total' => 10000,
            'currency' => 'usd',
            'metadata' => (object) [
                'order_id' => (string) $forOrder->id,
                'user_id' => (string) ($forUserId ?? $forOrder->user_id),
            ],
        ];

        $this->app->instance(StripeSessions::class, new class($session) extends StripeSessions {
            public function __construct(private object $session) {}

            public function retrieve(string $sessionId): object
            {
                return $this->session;
            }
        });
    }

    private function visit(User $as, Order $urlOrder, string $sessionId = 'cs_test_abc')
    {
        return Livewire::withQueryParams(['session_id' => $sessionId, 'order_id' => $urlOrder->id])
            ->actingAs($as)
            ->test(SuccessPage::class);
    }

    #[Test]
    public function the_order_the_session_was_created_for_is_credited(): void
    {
        $buyer = User::factory()->create();
        $order = $this->orderFor($buyer);
        $this->stripeSays($order);

        $this->visit($buyer, $order);

        $this->assertDatabaseHas('paiements', ['order_id' => $order->id, 'transaction_id' => 'stripe_cs_test_abc']);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    #[Test]
    public function the_url_cannot_redirect_a_paid_session_onto_another_order(): void
    {
        $buyer = User::factory()->create();
        $paidFor = $this->orderFor($buyer);
        $other = $this->orderFor($buyer);
        $this->stripeSays($paidFor);

        $this->visit($buyer, $other);

        $this->assertDatabaseMissing('paiements', ['order_id' => $other->id]);
        $this->assertSame('pending', $other->fresh()->payment_status);
        $this->assertSame('paid', $paidFor->fresh()->payment_status);
    }

    #[Test]
    public function a_session_created_for_someone_else_is_not_applied(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $order = $this->orderFor($owner);
        $this->stripeSays($order);

        $this->visit($intruder, $order);

        $this->assertDatabaseMissing('paiements', ['order_id' => $order->id]);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    #[Test]
    public function a_cancelled_order_is_not_credited(): void
    {
        $buyer = User::factory()->create();
        $order = $this->orderFor($buyer, ['status' => 'cancelled']);
        $this->stripeSays($order);

        $this->visit($buyer, $order);

        $this->assertDatabaseMissing('paiements', ['order_id' => $order->id]);
    }

    #[Test]
    public function an_order_still_under_negotiation_is_not_credited(): void
    {
        $buyer = User::factory()->create();
        $order = $this->orderFor($buyer, ['status' => Order::STATUS_NEGOTIATING]);
        $this->stripeSays($order);

        $this->visit($buyer, $order);

        $this->assertDatabaseMissing('paiements', ['order_id' => $order->id]);
    }

    #[Test]
    public function an_unpaid_session_records_nothing(): void
    {
        $buyer = User::factory()->create();
        $order = $this->orderFor($buyer);
        $this->stripeSays($order, status: 'unpaid');

        $this->visit($buyer, $order);

        $this->assertDatabaseMissing('paiements', ['order_id' => $order->id]);
    }

    #[Test]
    public function visiting_twice_never_credits_twice(): void
    {
        $buyer = User::factory()->create();
        $order = $this->orderFor($buyer);
        $this->stripeSays($order);

        $this->visit($buyer, $order);
        $this->visit($buyer, $order);

        $this->assertSame(1, $order->paiements()->count());
    }

    #[Test]
    public function a_payment_the_webhook_already_recorded_still_reads_as_confirmed(): void
    {
        $buyer = User::factory()->create();
        $order = $this->orderFor($buyer);
        $this->stripeSays($order);

        // The webhook got there first.
        $session = app(StripeSessions::class)->retrieve('cs_test_abc');
        app(StripePaymentRecorder::class)->record($session);

        $this->visit($buyer, $order)->assertSet('stripe_payment_completed', true);

        $this->assertSame(1, $order->paiements()->count());
    }

    #[Test]
    public function the_amount_is_converted_with_the_rate_frozen_on_the_order(): void
    {
        // 205 000 GNF at 0.00012 is $24.60. The vendor's rate has moved since the order
        // was placed; the payment must still settle the order exactly.
        $buyer = User::factory()->create();
        $gnf = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00030]);
        $vendor = Vendor::factory()->create(['currency_id' => $gnf->id]);
        $order = Order::create([
            'user_id' => $buyer->id, 'vendor_id' => $vendor->id,
            'order_number' => 'STRIPE-' . uniqid(), 'grand_total' => 205000,
            'total_paid' => 0, 'total_remaining' => 205000,
            'payment_method' => 'stripe', 'payment_status' => 'pending',
            'status' => 'new', 'rate_to_usd' => 0.00012,
        ]);
        $session = (object) [
            'id' => 'cs_test_gnf', 'payment_status' => 'paid', 'amount_total' => 2460, 'currency' => 'usd',
            'metadata' => (object) ['order_id' => (string) $order->id],
        ];

        app(StripePaymentRecorder::class)->record($session, $buyer->id);

        $order = $order->fresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(0, $order->total_remaining);
    }

    #[Test]
    public function a_session_not_charged_in_usd_is_left_for_a_human(): void
    {
        $buyer = User::factory()->create();
        $order = $this->orderFor($buyer);
        $session = (object) [
            'id' => 'cs_test_eur', 'payment_status' => 'paid', 'amount_total' => 10000, 'currency' => 'eur',
            'metadata' => (object) ['order_id' => (string) $order->id],
        ];

        $this->assertNull(app(StripePaymentRecorder::class)->record($session, $buyer->id));
        $this->assertDatabaseMissing('paiements', ['order_id' => $order->id]);
    }
}
