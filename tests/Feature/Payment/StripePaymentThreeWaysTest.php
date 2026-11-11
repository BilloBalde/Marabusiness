<?php

namespace Tests\Feature\Payment;

use App\Mail\PaymentConfirmed;
use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Services\StripePaymentRecorder;
use App\Support\StripeSessions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A Stripe payment can now reach the database three ways: the checkout success page, the
 * "pay later" return page (/success-stripe) and Stripe's webhook. They stored the
 * transaction id in two spellings (the bare session id, and 'stripe_<id>'), so the unique
 * index could not catch a second recording — a buyer whose payment the webhook had
 * already recorded was credited again when they landed on the return page. All three
 * now go through one recorder.
 */
class StripePaymentThreeWaysTest extends TestCase
{
    use RefreshDatabase;

    private const SESSION = 'cs_test_paylater';

    private function order(float $orderRate = 0.00012): Order
    {
        $gnf = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => $orderRate]);
        $vendor = Vendor::factory()->create(['currency_id' => $gnf->id]);

        return Order::create([
            'user_id' => User::factory()->create()->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'PL-' . uniqid(),
            'grand_total' => 205000,
            'total_paid' => 0,
            'total_remaining' => 205000,
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
            'status' => 'new',
            'rate_to_usd' => $orderRate,
            'stripe_session_id' => self::SESSION,
        ]);
    }

    /** What Stripe says about a "pay later" session, with the metadata PaiementModal writes. */
    private function stripeSays(Order $order, int $cents = 2460, float $sessionRate = 0.00012): object
    {
        $session = (object) [
            'id' => self::SESSION,
            'payment_status' => 'paid',
            'amount_total' => $cents,
            'currency' => 'usd',
            'metadata' => (object) [
                'order_id' => (string) $order->id,
                'rate' => (string) $sessionRate,
                'currency' => 'gnf',
            ],
        ];

        $this->app->instance(StripeSessions::class, new class($session) extends StripeSessions {
            public function __construct(private object $session) {}

            public function retrieve(string $sessionId): object
            {
                return $this->session;
            }
        });

        return $session;
    }

    private function returnPage(Order $order)
    {
        return $this->get('/success-stripe?session_id=' . self::SESSION . '&order_id=' . $order->id);
    }

    #[Test]
    public function the_pay_later_return_page_records_once_and_confirms_by_email_once(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->stripeSays($order);

        $this->returnPage($order)->assertOk();
        $this->returnPage($order)->assertOk();

        $this->assertSame(1, $order->paiements()->count());
        $this->assertSame('paid', $order->fresh()->payment_status);
        Mail::assertSent(PaymentConfirmed::class, 1);
    }

    #[Test]
    public function a_payment_the_webhook_recorded_is_not_recorded_again_by_the_return_page(): void
    {
        Mail::fake();
        $order = $this->order();
        $session = $this->stripeSays($order);

        app(StripePaymentRecorder::class)->record($session); // the webhook, first
        $this->returnPage($order)->assertOk();

        $this->assertSame(1, $order->paiements()->count());
        Mail::assertSent(PaymentConfirmed::class, 1); // sent by whoever recorded it, once
    }

    #[Test]
    public function the_return_page_first_then_the_webhook_records_nothing_more(): void
    {
        Mail::fake();
        $order = $this->order();
        $session = $this->stripeSays($order);

        $this->returnPage($order)->assertOk();
        app(StripePaymentRecorder::class)->record($session);

        $this->assertSame(1, $order->paiements()->count());
        Mail::assertSent(PaymentConfirmed::class, 1);
    }

    #[Test]
    public function the_rate_written_in_the_session_metadata_is_the_one_used_to_convert_back(): void
    {
        // The order was frozen at 0.00012 but the buyer paid it later, at the vendor's newer
        // rate of 0.00030: $61.50 is exactly 205 000 GNF at that rate. Converting it back
        // with the order's older rate would call it 512 500 GNF — an overpayment.
        Mail::fake();
        $order = $this->order(orderRate: 0.00012);
        $this->stripeSays($order, cents: 6150, sessionRate: 0.00030);

        $this->returnPage($order)->assertOk();

        $order = $order->fresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(205000, $order->total_paid);
        $this->assertEquals(205000, $order->paiements()->first()->amount);
    }

    #[Test]
    public function a_session_that_belongs_to_another_order_is_still_refused_by_the_return_page(): void
    {
        Mail::fake();
        $order = $this->order();
        $order->update(['stripe_session_id' => 'cs_test_someone_elses']);
        $this->stripeSays($order);

        $this->returnPage($order)->assertOk()->assertSee('Payment session does not match order');

        $this->assertSame(0, $order->paiements()->count());
        Mail::assertNothingSent();
    }
}
