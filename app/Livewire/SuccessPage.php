<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Paiement;
use App\Support\PaymentReconciliation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Support\Money;

#[Title('Success Page - MARA BUSINESS')]
class SuccessPage extends Component
{
    public $orders = [];
    public $session_id = null;
    public $order_id = null;
    public $stripe_payment_completed = false;

    public function mount()
    {
        $this->session_id = request()->query('session_id');
        $this->order_id = request()->query('order_id');
        
        $userId = Auth::id();

        // If we have a Stripe session_id, verify the payment
        if ($this->session_id) {
            $this->verifyStripePayment();
        }

        $this->loadOrders();
    }

    /**
     * Retrieve ALL orders created IN THIS SESSION.
     *
     * vendor.currency is eager loaded because both the cards and the day's total read
     * it for every order.
     */
    protected function loadOrders(): void
    {
        $this->orders = Order::with(['address', 'items', 'items.product', 'vendor.currency', 'latestShipment'])
            ->where('user_id', Auth::id())
            ->whereDate('created_at', now()->toDateString()) // same day
            ->latest()
            ->get();
    }

    /**
     * PaiementModal announces a payment; this page has to pick the change up.
     *
     * Without this the modal closed and the card underneath still read "En attente de
     * paiement" until the page was reloaded by hand — which is what made a payment that
     * had in fact gone through look like it had failed. The orders list already
     * listened for this event; this page never did.
     */
    #[On('payment-made')]
    public function refreshAfterPayment(): void
    {
        $this->loadOrders();
    }

    protected function verifyStripePayment()
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            
            // Retrieve the Stripe session
            $session = Session::retrieve($this->session_id);
            
            if ($session->payment_status === 'paid') {
                // Find the order by ID from query parameter or metadata
                $orderId = $this->order_id ?? $session->metadata->order_id ?? null;
                
                if ($orderId) {
                    $order = Order::find($orderId);
                    
                    if ($order && !$order->paiements()->where('payment_method', 'stripe')->exists()) {
                        // Calculate amounts
                        $totalAmount = $session->amount_total / 100; // Convert from cents
                        $currency = strtoupper($session->currency);
                        
                        // Convert USD to vendor currency if needed
                        $orderCurrency = $order->vendor?->currency?->code ?? 'USD';
                        $orderRate = $order->vendor?->currency?->rate_to_usd ?? 1;
                        
                        // `?? 1` above catches a missing currency row, not a rate
                        // of zero, and dividing by zero here is fatal while
                        // recording money Stripe has already collected.
                        $localAmount = $orderCurrency === 'USD'
                            ? $totalAmount
                            : Money::fromUsd((float) $totalAmount, (float) $orderRate);
                        
                        // Create payment record
                        Paiement::create([
                            'order_id' => $order->id,
                            'amount' => $localAmount,
                            'payment_method' => 'stripe',
                            'currency' => $orderCurrency,
                            'payment_status' => 'paid',
                            'transaction_id' => 'stripe_' . $session->id,
                            // Verified against Stripe itself, so this is money received,
                            // not money a buyer says they sent.
                            'confirmed_at' => now(),
                        ]);
                        
                        // Update order totals. Stripe only ever charges whole USD
                        // cents, so converting its charge back to the vendor's currency
                        // almost never reproduces the order's exact local total — a
                        // residual under one cent's worth counts as settled, not owed.
                        // Confirmed money only: a buyer's own unconfirmed declaration
                        // on this same order must not count towards Stripe's balance.
                        $totalPaid = $order->confirmedPaiements()->sum('amount');
                        $reconciled = PaymentReconciliation::resolve(
                            (float) $order->grand_total, (float) $totalPaid, $orderRate
                        );
                        
                        $order->update([
                            'total_paid' => $reconciled['total_paid'],
                            'total_remaining' => $reconciled['total_remaining'],
                            'payment_status' => $reconciled['payment_status'],
                        ]);
                        
                        // Update financial transactions
                        $this->updateFinancialTransactionStatus($order, 'stripe', $localAmount, 'Stripe payment completed');
                        
                        // Mark as completed
                        $this->stripe_payment_completed = true;
                        
                        Log::info('Stripe payment completed and recorded', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'session_id' => $session->id,
                            'stripe_amount' => $totalAmount,
                            'local_amount' => $localAmount,
                        ]);
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Stripe payment verification failed', [
                'session_id' => $this->session_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function updateFinancialTransactionStatus($order, $paymentMethod, $amount, $notes = '')
    {
        try {
            // Find and update the pending financial transaction
            $transaction = \App\Models\FinancialTransaction::where('order_id', $order->id)
                ->where('status', 'pending')
                ->first();
            
            if ($transaction) {
                $transaction->update([
                    'status' => \App\Models\FinancialTransaction::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'notes' => $notes . ' - Amount: ' . $amount . ' ' . $paymentMethod,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update financial transaction status', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The day's total, and the currency it is honestly expressed in.
     *
     * The footer used to print `Number::currency($orders->sum('grand_total'), 'USD')`:
     * grand_total is stored in each vendor's own currency, so a 290 000 GNF order was
     * labelled "$290,000.00" — the same figure with a dollar sign in front of it, about
     * 8 000 times its real value ($34.80). And because a buyer can order from vendors
     * billing in different currencies on the same day (several already have), adding
     * the raw totals together is meaningless in the first place.
     *
     * One currency across the day's orders: total in that currency, like the cards
     * above. Several: everything converted to USD, the pivot used elsewhere in the app.
     *
     * @return array{0: float, 1: string}
     */
    protected function todayTotal(): array
    {
        $orders = $this->orders ?? collect();

        $currencies = $orders
            ->map(fn ($order) => $order->vendor?->currency?->code)
            ->filter()
            ->unique();

        if ($currencies->count() === 1) {
            return [(float) $orders->sum('grand_total'), $currencies->first()];
        }

        $usd = $orders->sum(function ($order) {
            // grand_total_usd is only filled on part of the history, so fall back to the
            // rate stored on the order, then to the vendor's current rate.
            if ((float) $order->grand_total_usd > 0) {
                return (float) $order->grand_total_usd;
            }

            $rate = (float) ($order->rate_to_usd ?: $order->vendor?->currency?->rate_to_usd ?: 1);

            return (float) $order->grand_total * $rate;
        });

        return [$usd, 'USD'];
    }

    public function render()
    {
        [$todayTotal, $todayTotalCurrency] = $this->todayTotal();

        return view('livewire.success-page', [
            'orders' => $this->orders,
            'stripe_payment_completed' => $this->stripe_payment_completed,
            'session_id' => $this->session_id,
            'todayTotal' => $todayTotal,
            'todayTotalCurrency' => $todayTotalCurrency,
        ]);
    }
}