<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Paiement;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LengoPayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('LengoPay callback RAW', $request->all());

        Log::info('LengoPay webhook HIT', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'raw' => $request->getContent(),
        ]);
        // LengoPay callback payload
        $data = $request->validate([
            'pay_id'  => ['required','string'],
            'status'  => ['required','string'], // SUCCESS / FAILED
            'amount'  => ['required','numeric'],
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

        $status = strtoupper(trim($data['status']));

        // If FAILED -> mark failed and exit
        if ($status !== 'SUCCESS') {
            $order->update([
                'payment_status' => 'failed',
                'payment_method' => 'lengopay',
            ]);

            FinancialTransaction::where('order_id', $order->id)
                ->update(['status' => FinancialTransaction::STATUS_PENDING]);

            Log::info('LengoPay callback FAILED', [
                'order_id' => $order->id,
                'pay_id'   => $data['pay_id'],
                'status'   => $data['status'],
                'amount'   => $data['amount'],
            ]);

            return response()->json(['ok' => true]);
        }

        // 3) SUCCESS -> create Paiement record
        // Assumption: LengoPay "amount" is in the order's local currency (ex: GNF)
        // If you store vendor currency code in vendor->currency->code, use it.
        $currency = $order->currency
            ?? optional($order->vendor?->currency)->code
            ?? 'GNF';

        $amountPaid = (float) $data['amount'];

        $payment = Paiement::create([
            'order_id'       => $order->id,
            'amount'         => $amountPaid,
            'payment_method' => 'lengopay',
            'currency'       => $currency,
            'payment_status' => 'paid',
            'transaction_id' => $data['pay_id'],   // important: use pay_id for idempotency
            // If you have a field for gateway message, you can store it in metadata/notes
            // 'note' => $data['message'] ?? null,
        ]);

        // 4) Update order totals (same logic as Stripe success page)
        $totalPaid = $order->paiements()
            ->where('payment_status', 'paid')
            ->sum('amount');

        $totalRemaining = max(0, $order->grand_total - $totalPaid);

        $paymentStatus = $totalRemaining <= 0 ? 'paid' : 'partial';

        $order->update([
            'total_paid'      => $totalPaid,
            'total_remaining' => $totalRemaining,
            'payment_status'  => $paymentStatus,
            'payment_method'  => 'lengopay',
        ]);

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
