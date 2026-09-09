<?php

namespace Tests\Feature\Security;

use App\Models\Order;
use App\Models\Paiement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression tests for the LengoPay webhook payment-forgery bug: the endpoint used to
 * trust the 'status' and 'amount' fields of the POST body directly — anyone who had
 * seen a pay_id (the buyer's own browser sees it during the redirect flow) could POST
 * a fake "SUCCESS" and get their order marked paid without ever paying.
 *
 * The fix makes the webhook body a lookup key only: it now calls LengoPayService::
 * getPaymentStatus() to ask LengoPay's own API for the real status before marking
 * anything paid, exactly like SuccessPage::verifyStripePayment() already does for
 * Stripe by calling Session::retrieve() rather than trusting the client.
 */
class LengoPayWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function orderAwaitingLengoPay(string $payId): Order
    {
        return Order::factory()->create([
            'lengopay_pay_id' => $payId,
            'payment_status' => 'pending',
            'grand_total' => 100,
        ]);
    }

    #[Test]
    public function a_forged_webhook_claiming_success_is_rejected_when_lengopay_itself_says_failed(): void
    {
        $order = $this->orderAwaitingLengoPay('forged-pay-id');

        Http::fake([
            '*/api/v1/payments/forged-pay-id' => Http::response(['status' => 'FAILED', 'amount' => 0]),
        ]);

        // An attacker who has seen their own pay_id posts a fabricated SUCCESS.
        $response = $this->postJson('/api/payments/lengopay/callback', [
            'pay_id' => 'forged-pay-id',
            'status' => 'SUCCESS',
            'amount' => 999999,
        ]);

        $response->assertOk();

        $order->refresh();
        $this->assertSame('failed', $order->payment_status);
        $this->assertDatabaseMissing('paiements', ['transaction_id' => 'forged-pay-id']);
    }

    #[Test]
    public function a_genuine_success_confirmed_by_lengopay_marks_the_order_paid(): void
    {
        $order = $this->orderAwaitingLengoPay('genuine-pay-id');

        Http::fake([
            '*/api/v1/payments/genuine-pay-id' => Http::response(['status' => 'SUCCESS', 'amount' => 100]),
        ]);

        $response = $this->postJson('/api/payments/lengopay/callback', [
            'pay_id' => 'genuine-pay-id',
            'status' => 'SUCCESS',
            'amount' => 100,
        ]);

        $response->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertDatabaseHas('paiements', [
            'transaction_id' => 'genuine-pay-id',
            'payment_status' => 'paid',
        ]);
    }

    #[Test]
    public function an_unreachable_lengopay_api_fails_closed_instead_of_trusting_the_payload(): void
    {
        $order = $this->orderAwaitingLengoPay('unreachable-pay-id');

        Http::fake([
            '*/api/v1/payments/unreachable-pay-id' => Http::response(null, 500),
        ]);

        $response = $this->postJson('/api/payments/lengopay/callback', [
            'pay_id' => 'unreachable-pay-id',
            'status' => 'SUCCESS',
            'amount' => 100,
        ]);

        // The endpoint still answers 200 (so LengoPay does not hammer it with
        // retries), but nothing gets marked paid on an unverifiable claim.
        $response->assertOk();

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
        $this->assertDatabaseMissing('paiements', ['transaction_id' => 'unreachable-pay-id']);
    }

    #[Test]
    public function replaying_an_already_processed_pay_id_does_not_create_a_second_payment(): void
    {
        $order = $this->orderAwaitingLengoPay('replayed-pay-id');

        Paiement::create([
            'order_id' => $order->id,
            'amount' => 100,
            'payment_method' => 'lengopay',
            'currency' => 'GNF',
            'payment_status' => 'paid',
            'transaction_id' => 'replayed-pay-id',
        ]);

        Http::fake([
            '*/api/v1/payments/replayed-pay-id' => Http::response(['status' => 'SUCCESS', 'amount' => 100]),
        ]);

        $this->postJson('/api/payments/lengopay/callback', [
            'pay_id' => 'replayed-pay-id',
            'status' => 'SUCCESS',
            'amount' => 100,
        ])->assertOk();

        $this->assertSame(1, Paiement::where('transaction_id', 'replayed-pay-id')->count());
        // The verification call is never even reached for an already-processed pay_id.
        Http::assertNothingSent();
    }

    #[Test]
    public function a_confirmed_cash_payment_already_on_the_order_is_not_dropped_from_the_balance(): void
    {
        // The webhook used to sum ->where('payment_status', 'paid')->sum('amount'),
        // which silently excluded a confirmed cash-on-delivery payment recorded with
        // status 'partial' (true on its own, since it didn't cover the order alone).
        // That divergence from Order::confirmedPaiements() left a real order
        // (INV2026090006) showing a balance due that was already collected.
        $order = $this->orderAwaitingLengoPay('mixed-pay-id');
        $order->update(['grand_total' => 1055450]);

        Paiement::create([
            'order_id' => $order->id,
            'amount' => 1000000,
            'payment_method' => 'cod',
            'currency' => 'GNF',
            'payment_status' => 'partial',
            'transaction_id' => 'TRANS-cash-leg',
            'confirmed_at' => now(),
        ]);

        Http::fake([
            '*/api/v1/payments/mixed-pay-id' => Http::response(['status' => 'SUCCESS', 'amount' => 55450]),
        ]);

        $this->postJson('/api/payments/lengopay/callback', [
            'pay_id' => 'mixed-pay-id',
            'status' => 'SUCCESS',
            'amount' => 55450,
        ])->assertOk();

        $order->refresh();
        $this->assertSame(1055450.0, (float) $order->total_paid);
        $this->assertSame(0.0, (float) $order->total_remaining);
        $this->assertSame('paid', $order->payment_status);
    }
}
