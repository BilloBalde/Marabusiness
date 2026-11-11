<?php

namespace App\Livewire;

use App\Models\Order;
use App\Services\StripePaymentRecorder;
use App\Support\StripeSessions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

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

    /**
     * Asks Stripe about the session in the URL and records it — for the order the
     * session itself names, and only when the signed-in buyer owns that order.
     *
     * The `order_id` in the URL is deliberately not consulted: it is the one part of this
     * request the buyer controls, and it used to decide which order got the money.
     * The Stripe webhook records the same payments, so this page may well find it
     * already done; the banner reads "confirmed" either way.
     */
    protected function verifyStripePayment(): void
    {
        try {
            $session = app(StripeSessions::class)->retrieve($this->session_id);

            $order = app(StripePaymentRecorder::class)->record($session, Auth::id());

            $this->stripe_payment_completed = $order !== null;
        } catch (\Throwable $e) {
            Log::error('Stripe payment verification failed', [
                'session_id' => $this->session_id,
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
