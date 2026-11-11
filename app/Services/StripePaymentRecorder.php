<?php

namespace App\Services;

use App\Mail\PaymentConfirmed;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\Paiement;
use App\Support\Money;
use App\Support\PaymentReconciliation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Turns a paid Stripe Checkout Session into a payment on the order it was created for.
 *
 * Shared by the success page (the buyer coming back from Stripe) and the Stripe webhook
 * (Stripe telling us directly), so that whichever arrives first records the money and the
 * other finds it already there. Before the webhook existed a buyer who paid and then
 * closed the tab was charged and never credited.
 *
 * The session decides which order is paid, never the request: the order id comes from
 * the metadata our own server wrote when it created the session. The `?order_id=` in
 * the return URL used to win over it, which let one genuinely paid session be pointed
 * at any other order — a stranger's, a cancelled one, one still under negotiation.
 */
class StripePaymentRecorder
{
    /**
     * The buyer gets a "Paiement confirmé" e-mail once, when THIS call is the one that
     * records the payment — not when it was already on record. Before the webhook only the
     * "pay later" return page sent it, so a buyer whose payment the webhook recorded
     * first would never have heard.
     *
     * @param  object    $session        A Checkout Session (retrieved or from a webhook event).
     * @param  int|null  $onlyForUserId  When given, the order must belong to this user —
     *                                   the success page passes the signed-in buyer; the
     *                                   webhook (authenticated by Stripe's signature) does not.
     * @return Order|null  The order this session pays, when its money is on record after
     *                     this call (recorded now or already there); null when the session
     *                     cannot be applied.
     */
    public function record(object $session, ?int $onlyForUserId = null): ?Order
    {
        if (($session->payment_status ?? null) !== 'paid') {
            return null;
        }

        // Every session this application creates is charged in USD. Anything else would
        // be recorded as a wrong amount, so it is left for a human.
        if (strtolower((string) ($session->currency ?? 'usd')) !== 'usd') {
            Log::error('Stripe session not in USD: not recorded', ['session_id' => $session->id ?? null]);

            return null;
        }

        $orderId = $session->metadata->order_id ?? null;
        $order = $orderId ? Order::with('vendor.currency')->find($orderId) : null;

        if (! $order) {
            return null;
        }

        if ($onlyForUserId !== null && (int) $order->user_id !== $onlyForUserId) {
            Log::warning('Stripe session presented by someone other than the order\'s buyer', [
                'session_id' => $session->id,
                'order_id' => $order->id,
            ]);

            return null;
        }

        if ($order->status === 'cancelled' || $order->isNegotiating()) {
            // Money has been taken for an order that cannot take it: not credited, and
            // loud enough for someone to refund it.
            Log::warning('Stripe payment received for an order that can no longer be paid', [
                'session_id' => $session->id,
                'order_id' => $order->id,
                'status' => $order->status,
            ]);

            return null;
        }

        // Two spellings exist in the data: the checkout page stored 'stripe_<id>', the
        // "pay later" flow stored the bare id.
        $transactionIds = ['stripe_' . $session->id, $session->id];

        if (Paiement::whereIn('transaction_id', $transactionIds)->exists()) {
            return $order;
        }

        $currencyCode = $order->vendor?->currency?->code ?? 'USD';

        // The rate the Stripe amount was built with, so converting back is its exact
        // inverse. "Pay later" sessions (order page, mobile) write that rate into the
        // metadata — it is the vendor's rate at that moment, which may differ from the
        // order's. Checkout sessions do not, and there the order's own frozen rate is the
        // one the amount came from. The vendor's current rate is only a last resort.
        // The metadata is written by this server when it creates the session: it is not
        // something the buyer can alter.
        $rate = (float) ($session->metadata->rate ?? 0);

        if ($rate <= 0) {
            $rate = (float) ($order->rate_to_usd ?: ($order->vendor?->currency?->rate_to_usd ?: 1));
        }

        $usdAmount = ((float) $session->amount_total) / 100;

        // `?: 1` above catches a missing rate, and a zero one: dividing by it here would
        // be fatal in the middle of recording money Stripe has already taken.
        $localAmount = strtoupper($currencyCode) === 'USD'
            ? $usdAmount
            : Money::fromUsd($usdAmount, $rate);

        $payment = null;

        try {
            DB::transaction(function () use ($order, $session, $currencyCode, $localAmount, $rate, &$payment) {
                $payment = Paiement::create([
                    'order_id' => $order->id,
                    'amount' => $localAmount,
                    'payment_method' => 'stripe',
                    'currency' => $currencyCode,
                    'payment_status' => 'paid',
                    'transaction_id' => 'stripe_' . $session->id,
                    // Verified against Stripe itself, so this is money received, not money
                    // a buyer says they sent.
                    'confirmed_at' => now(),
                ]);

                // Stripe only ever charges whole USD cents, so converting its charge back
                // to the vendor's currency almost never reproduces the order's exact local
                // total — a residual under one cent's worth counts as settled, not owed.
                // Confirmed money only, converted into the order's own currency first.
                $reconciled = PaymentReconciliation::resolve(
                    (float) $order->grand_total, $order->paidAmount(), $rate
                );

                $order->update([
                    'total_paid' => $reconciled['total_paid'],
                    'total_remaining' => $reconciled['total_remaining'],
                    'payment_status' => $reconciled['payment_status'],
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // The webhook and the success page arrived together and the other one won.
            return $order;
        }

        $this->settleFinancialTransaction($order);

        Log::info('Stripe payment recorded', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'session_id' => $session->id,
            'local_amount' => $localAmount,
        ]);

        $this->sendConfirmation($order->fresh(['user']), $payment);

        return $order;
    }

    /** Never allowed to undo a payment that is already recorded. */
    private function sendConfirmation(Order $order, Paiement $payment): void
    {
        try {
            if ($order->user?->email) {
                Mail::to($order->user->email)->send(new PaymentConfirmed($order, $payment));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send payment confirmation email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function settleFinancialTransaction(Order $order): void
    {
        try {
            $transaction = FinancialTransaction::where('order_id', $order->id)
                ->where('status', FinancialTransaction::STATUS_PENDING)
                ->first();

            $transaction?->update([
                'status' => FinancialTransaction::STATUS_PROCESSED,
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update financial transaction status', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
