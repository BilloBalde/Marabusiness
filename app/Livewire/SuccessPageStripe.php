<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Paiement;
use App\Support\PaymentReconciliation;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Mail;

class SuccessPageStripe extends Component
{
    public $order;
    public $sessionId;
    public $orderId;
    public $payment;
    public $processing = true;
    public $error = null;

    public function mount()
    {
        $this->sessionId = request('session_id');
        $this->orderId = request('order_id');

        if (!$this->sessionId) {
            $this->error = "Missing session ID";
            $this->processing = false;
            return;
        }

        // First check if payment already exists
        $this->payment = Paiement::where('transaction_id', $this->sessionId)->first();
        
        if ($this->payment) {
            // Payment already processed
            $this->order = $this->payment->order;
            $this->processing = false;
            Log::info('Payment already exists', [
                'session_id' => $this->sessionId,
                'payment_id' => $this->payment->id,
            ]);
        } else {
            // Need to verify and create payment
            $this->verifyAndCreatePayment();
        }
    }

    private function verifyAndCreatePayment()
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            
            // Retrieve Stripe session
            $session = Session::retrieve($this->sessionId);
            
            // Check if payment was successful
            if ($session->payment_status !== 'paid') {
                $this->error = "Payment not completed. Status: " . $session->payment_status;
                $this->processing = false;
                Log::warning('Payment not completed', [
                    'session_id' => $this->sessionId,
                    'payment_status' => $session->payment_status,
                ]);
                return;
            }

            // Get order from metadata or from URL parameter
            $metadata = $session->metadata;
            
            if (!empty($metadata->order_id)) {
                $this->orderId = $metadata->order_id;
            }
            
            if (!$this->orderId) {
                $this->error = "Order ID not found in payment session";
                $this->processing = false;
                return;
            }

            // Find the order
            $this->order = Order::find($this->orderId);
            
            if (!$this->order) {
                $this->error = "Order not found";
                $this->processing = false;
                return;
            }

            // Verify this session belongs to this order
            if ($this->order->stripe_session_id !== $this->sessionId) {
                $this->error = "Payment session does not match order";
                $this->processing = false;
                Log::error('Session mismatch', [
                    'order_session' => $this->order->stripe_session_id,
                    'current_session' => $this->sessionId,
                ]);
                return;
            }

            // Extract payment details from metadata
            $amountUsd = $metadata->amount_usd ?? ($session->amount_total / 100);
            $currency = $metadata->currency ?? ($this->order->vendor->currency->code ?? 'USD');
            $rate = $metadata->rate ?? ($this->order->vendor->currency->rate_to_usd ?? 1);
            
            // FIXED: Calculate local amount from USD amount
            // If amountUsd = 1.2 USD and rate = 0.00012 (for GNF)
            // Then: 1.2 / 0.00012 = 10,000 GNF
            $amountLocal = $amountUsd / $rate;

            Log::info('Stripe payment conversion', [
                'order_id' => $this->order->id,
                'amount_usd' => $amountUsd,
                'rate' => $rate,
                'calculated_amount_local' => $amountLocal,
                'order_total_remaining' => $this->order->total_remaining,
            ]);

            // Create the payment record
            $this->payment = Paiement::create([
                'order_id' => $this->order->id,
                'amount' => $amountLocal,
                'payment_method' => 'stripe',
                'currency' => $currency,
                'payment_status' => 'paid',
                'transaction_id' => $this->sessionId,
                'stripe_payment_intent' => $session->payment_intent,
                // Verified against Stripe itself: received, not merely declared.
                'confirmed_at' => now(),
            ]);

            Log::info('Stripe payment created', [
                'order_id' => $this->order->id,
                'payment_id' => $this->payment->id,
                'amount_local' => $amountLocal,
                'amount_usd' => $amountUsd,
                'currency' => $currency,
                'rate' => $rate,
            ]);

            // Update order totals. Stripe only ever charges whole USD cents, so
            // converting its charge back to the vendor's currency almost never
            // reproduces the order's exact local total — a residual under one cent's
            // worth counts as settled, not owed.
            // Confirmed money only, not filtered on payment_status='paid': a
            // partial cash-on-delivery payment on the same order is real money the
            // vendor confirmed receiving, and belongs in this balance too. Filtering
            // on 'paid' silently dropped it — this is the divergence that left a
            // real order (INV2026090006) showing a balance due that was already
            // collected.
            $totalPaid = $this->order->confirmedPaiements()->sum('amount');
            $reconciled = PaymentReconciliation::resolve(
                (float) $this->order->grand_total, (float) $totalPaid, $rate
            );

            $this->order->update([
                'total_paid' => $reconciled['total_paid'],
                'total_remaining' => $reconciled['total_remaining'],
                'payment_status' => $reconciled['payment_status'],
                'payment_method' => 'stripe',
            ]);

            // Send confirmation email
            $this->sendConfirmationEmail();

            $this->processing = false;

        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $this->error = "Invalid payment session: " . $e->getMessage();
            $this->processing = false;
            Log::error('Stripe session error', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->error = "Payment processing error: " . $e->getMessage();
            $this->processing = false;
            Log::error('Payment processing error', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function sendConfirmationEmail()
    {
        try {
            if ($this->order && $this->order->user) {
                Mail::to($this->order->user->email)->send(new \App\Mail\PaymentConfirmed($this->order, $this->payment));
                Log::info('Payment confirmation email sent', [
                    'order_id' => $this->order->id,
                    'user_email' => $this->order->user->email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation email', [
                'order_id' => $this->order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.success-stripe', [
            'order' => $this->order,
            'payment' => $this->payment,
            'processing' => $this->processing,
            'error' => $this->error,
            'sessionId' => $this->sessionId,
        ]);
    }
}