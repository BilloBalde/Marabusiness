<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Order;
use App\Models\Paiement;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Illuminate\Support\Facades\Log;

class PaiementModal extends Component
{
    use WithFileUploads;

    public $showModal = false;
    public $order;
    public $amount;
    public $image;
    public $payment_method = '';
    public $currency = 'USD';

    #[On('open-paiement-modal')]
    public function openModal($orderId)
    {
        $this->order = Order::findOrFail($orderId);
        $this->payment_method = '';
        $this->amount = null;
        $this->image = null;
        $this->showModal = true;
    }

    protected $rules = [
        'payment_method' => 'required|string',
        'amount'         => 'nullable|numeric|min:1',
        'image'          => 'nullable|image|max:2048',
    ];

    public function save()
    {
        $this->validate();

        $order = $this->order;

        // ======================================================
        // 🚀 1) STRIPE PAYMENT — REDIRECT IMMEDIATELY
        // ======================================================
        if ($this->payment_method === 'stripe') {

            $amountRemaining = $order->total_remaining;
            $currency = strtolower(
                $order->currency
                ?? optional($order->vendor?->currency)->code
                ?? 'usd'
            );
            $rate = $order->vendor?->currency->rate_to_usd;
            $usdAmount = $amountRemaining * $rate;

            $stripeAmount = $currency == 'usd' ? intval($amountRemaining * 100) : intval($amountRemaining * $rate * 100);

            // Validate Stripe amount limits
            if ($stripeAmount <= 0) {
                $this->addError('amount', 'Invalid payment amount.');
                return;
            }
            
            if ($stripeAmount > 99999999) {
                $this->addError('amount', 'Payment amount exceeds Stripe limit. Please contact support.');
                return;
            }

            Stripe::setApiKey(env('STRIPE_SECRET'));

            try {
                $session = Session::create([
                    'payment_method_types' => ['card'],
                    'customer_email'       => auth()->user()->email,
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => "Paiement commande #{$order->order_number}",
                            ],
                            'unit_amount' => $stripeAmount,
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => route('success.stripe') . '?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order->id,
                    'cancel_url'  => route('my-orders.show', $order->id). '?canceled=true',
                    'metadata'    => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'amount_usd' => $usdAmount,
                        'amount_local' => $amountRemaining,
                        'currency' => $currency,
                        'rate' => $rate,
                    ],
                ]);
                //dd($session->id);

                // ONLY save Stripe session ID to order, NO payment record
                $order->update([
                    'stripe_session_id' => $session->id,
                    'payment_method' => 'stripe',
                    // DO NOT update total_paid or total_remaining here
                ]);

                //dd($order);

                Log::info('Stripe session created', [
                    'order_id' => $order->id,
                    'session_id' => $session->id,
                    'amount_usd' => $usdAmount,
                    'amount_local' => $amountRemaining,
                ]);

                // 🚀 Trigger JS redirect
                $this->dispatch('redirect-stripe', url: $session->url);
                
                // Close modal
                $this->reset(['amount', 'image', 'payment_method', 'showModal']);
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                Log::error('Stripe session creation failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                
                $this->addError('payment_method', 'Payment gateway error: ' . $e->getMessage());
            }

            return;
        }

        // ======================================================
        // 🟡 2) OFFLINE PAYMENTS (OM / COD)
        // ======================================================

        $imagePath = $this->image
            ? $this->image->store('payments', 'public')
            : null;

        $vendor      = $order->vendor;
        $currency    = $vendor->currency->code ?? 'USD';
        $rate        = $vendor->rate_to_usd ?? 1;

        $paidUsd     = $this->amount / $rate;
        $totalUsd    = $order->grand_total / $rate;

        Paiement::create([
            'order_id'       => $order->id,
            'amount'         => $this->amount,
            'image'          => $imagePath,
            'payment_method' => $this->payment_method,
            'currency'       => $currency,
            'payment_status' => $paidUsd >= $totalUsd ? 'paid' : 'partial',
            'transaction_id' => Order::generateTransactionNumber(),
        ]);

        // Update order totals
        $order->update([
            'total_paid'      => $order->paiements()->sum('amount'),
            'total_remaining' => max(0, $order->grand_total - $order->paiements()->sum('amount')),
        ]);

        $this->reset(['amount', 'image', 'payment_method', 'showModal']);
        $this->dispatch('payment-made');
        $this->dispatch('close-modal');
    }

    public function render()
    {
        return view('livewire.paiement-modal');
    }
}
