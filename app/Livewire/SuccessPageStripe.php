<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Paiement;
use App\Services\StripePaymentRecorder;
use App\Support\StripeSessions;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

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

        // First check if payment already exists. Two spellings of the transaction id
        // exist: the bare session id written by this page, and 'stripe_<id>' written by
        // the checkout success page and the Stripe webhook. Looking for only one let a
        // payment the webhook had already recorded be recorded a second time here.
        $this->payment = Paiement::whereIn('transaction_id', [$this->sessionId, 'stripe_' . $this->sessionId])->first();

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
            // Retrieve Stripe session
            $session = app(StripeSessions::class)->retrieve($this->sessionId);

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

            // The recording itself — amount conversion, totals, the unique transaction id,
            // the checks on the order's state and the confirmation e-mail — is shared with
            // the checkout success page and the Stripe webhook, so the same payment is never
            // recorded twice whichever of the three gets there first.
            $recorded = app(StripePaymentRecorder::class)->record($session);

            if (! $recorded) {
                $this->error = "Payment could not be applied to this order";
                $this->processing = false;
                return;
            }

            $this->order = $recorded->fresh();
            $this->payment = Paiement::whereIn('transaction_id', [$this->sessionId, 'stripe_' . $this->sessionId])->first();

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
