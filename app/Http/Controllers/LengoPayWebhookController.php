<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Paiement;
use App\Models\FinancialTransaction;
use App\Services\LengoPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LengoPayWebhookController extends Controller
{
    public function __construct(private LengoPayService $lengoPay)
    {
    }

    public function handle(Request $request)
    {
        // Was two near-identical Log::info calls back to back — the second's
        // 'payload' key was the exact same $request->all() as the first, just
        // wrapped alongside the full request headers. One entry carries
        // everything either one did; the raw body is dropped since it's already
        // the same content as 'payload' for this endpoint's JSON/form posts.
        Log::info('LengoPay webhook received', [
            'payload' => $request->all(),
            'content_type' => $request->header('Content-Type'),
        ]);

        // LengoPay callback payload — used only to know WHICH payment to look up.
        // Its own 'status'/'amount' are never trusted: this endpoint is public and
        // unsigned, so anyone who has seen a pay_id (the buyer's own browser sees it
        // during the redirect flow) could otherwise POST a fake SUCCESS here and get
        // their order marked paid for free. See the verification call below.
        $data = $request->validate([
            'pay_id'  => ['required','string'],
            'status'  => ['nullable','string'], // SUCCESS / FAILED — informational only
            'amount'  => ['nullable','numeric'], // informational only
            'message' => ['nullable','string'],
            'Client'  => ['nullable','string'],
        ]);

        // 1) Find the order by pay_id saved at init time
        $order = Order::where('lengopay_pay_id', $data['pay_id'])->first();

        if (!$order) {
            Log::warning('LengoPay callback: order not found', $data);
            return response()->json(['ok' => true]);
        }

        // 2) If payment already exists, do nothing (idempotent)
        $existingPayment = Paiement::where('transaction_id', $data['pay_id'])->first();
        if ($existingPayment) {
            Log::info('LengoPay callback already processed', [
                'order_id' => $order->id,
                'pay_id' => $data['pay_id'],
                'payment_id' => $existingPayment->id,
            ]);
            return response()->json(['ok' => true]);
        }

        // 2b) Ask LengoPay's own API for the real status of this pay_id — the webhook
        // body itself is never authoritative. Same principle as the Stripe flow
        // (SuccessPage::verifyStripePayment(), which calls Session::retrieve() rather
        // than trusting the client): only a value fetched server-to-server, using the
        // license key nobody else has, can be trusted to decide whether an order gets
        // marked paid. If this call fails or is unreachable, we fail closed — log it
        // and stop, rather than falling back to the unverified payload.
        try {
            $verified = $this->lengoPay->getPaymentStatus($data['pay_id']);
        } catch (\Throwable $e) {
            Log::error('LengoPay callback: could not verify pay_id against LengoPay API', [
                'order_id' => $order->id,
                'pay_id'   => $data['pay_id'],
                'error'    => $e->getMessage(),
            ]);

            return response()->json(['ok' => true]);
        }

        $status = strtoupper(trim((string) ($verified['status'] ?? '')));

        // If FAILED -> mark failed and exit
        if ($status !== 'SUCCESS') {
            $order->update([
                'payment_status' => 'failed',
                'payment_method' => 'lengopay',
            ]);

            FinancialTransaction::where('order_id', $order->id)
                ->update(['status' => FinancialTransaction::STATUS_PENDING]);

            Log::info('LengoPay callback FAILED (per verified status)', [
                'order_id'      => $order->id,
                'pay_id'        => $data['pay_id'],
                'verified'      => $verified,
                'payload_claim' => $data['status'] ?? null,
            ]);

            return response()->json(['ok' => true]);
        }

        // 3) SUCCESS -> create Paiement record
        // Assumption: LengoPay "amount" is in the order's local currency (ex: GNF)
        // If you store vendor currency code in vendor->currency->code, use it.
        $currency = $order->currency
            ?? optional($order->vendor?->currency)->code
            ?? 'GNF';

        // Amount also comes from the verified response, never from the raw payload.
        $amountPaid = (float) ($verified['amount'] ?? $data['amount'] ?? 0);

        $payment = Paiement::create([
            'order_id'       => $order->id,
            'amount'         => $amountPaid,
            'payment_method' => 'lengopay',
            'currency'       => $currency,
            'payment_status' => 'paid',
            'transaction_id' => $data['pay_id'],   // important: use pay_id for idempotency
            // The status was just verified against LengoPay's own API above, so this is
            // confirmed money and needs no further human check.
            'confirmed_at'   => now(),
            // If you have a field for gateway message, you can store it in metadata/notes
            // 'note' => $data['message'] ?? null,
        ]);

        // 4) Update order totals from confirmed payments only. Filtering on
        // payment_status='paid' silently dropped a confirmed cash-on-delivery or
        // Orange Money payment sitting on the same order — see Order::syncPaymentTotals().
        $order->update(['payment_method' => 'lengopay']);
        $order->syncPaymentTotals();
        $order->refresh();
        $paymentStatus = $order->payment_status;

        // 5) Update financial transactions
        if ($paymentStatus === 'paid') {
            FinancialTransaction::where('order_id', $order->id)
                ->update([
                    'status' => FinancialTransaction::STATUS_PROCESSED,
                    'processed_at' => now(),
                ]);
        } else {
            // partial payment
            FinancialTransaction::where('order_id', $order->id)
                ->update([
                    'status' => FinancialTransaction::STATUS_PENDING,
                    'processed_at' => null,
                ]);
        }

        // 6) Send confirmation email (like your Stripe success)
        try {
            if ($order->user) {
                Mail::to($order->user->email)->send(
                    new \App\Mail\PaymentConfirmed($order, $payment)
                );

                Log::info('LengoPay payment confirmation email sent', [
                    'order_id' => $order->id,
                    'user_email' => $order->user->email,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send LengoPay payment confirmation email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('LengoPay callback SUCCESS processed', [
            'order_id' => $order->id,
            'pay_id'   => $data['pay_id'],
            'amount'   => $amountPaid,
            'currency' => $currency,
            'payment_id' => $payment->id,
            'payment_status' => $paymentStatus,
        ]);

        return response()->json(['ok' => true]);
    }
}
