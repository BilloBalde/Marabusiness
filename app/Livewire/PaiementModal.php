<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Order;
use App\Models\Paiement;
use App\Models\FinancialTransaction;
use App\Services\FinanceCalculator;
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

    private $financeCalculator;

    public function __construct()
    {
        $this->financeCalculator = new FinanceCalculator();
    }

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

                $this->updateOrderFinancialTransactionsForPayment($order, 'stripe', 0, 'Stripe payment initiated');

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

        $this->updateOrderFinancialTransactionsForPayment($order, $this->payment_method, $this->amount, 'Payment received');

        $this->reset(['amount', 'image', 'payment_method', 'showModal']);
        $this->dispatch('payment-made');
        $this->dispatch('close-modal');
    }

    /** -------------------------------------------------------------
     *   UPDATE FINANCIAL TRANSACTIONS FOR PAYMENT
     * ------------------------------------------------------------- */
    private function updateOrderFinancialTransactionsForPayment($order, $paymentMethod, $amountPaid, $description)
    {
        $totalAmount = $order->grand_total;
        $paymentPercentage = $amountPaid > 0 ? ($amountPaid / $totalAmount) * 100 : 0;
        
        // Find the revenue transaction for this order
        $revenueTransaction = FinancialTransaction::where('order_id', $order->id)
            ->where('transaction_type', FinancialTransaction::TYPE_ORDER)
            ->first();
            
        if (!$revenueTransaction) {
            // Create financial transactions if they don't exist
            $this->createFinancialTransactionsForExistingOrder($order);
            $revenueTransaction = FinancialTransaction::where('order_id', $order->id)
                ->where('transaction_type', FinancialTransaction::TYPE_ORDER)
                ->first();
        }
        
        if ($revenueTransaction) {
            if ($amountPaid >= $totalAmount) {
                // Full payment received
                $revenueTransaction->update([
                    'status' => FinancialTransaction::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'description' => "Order #{$order->order_number} - Payment completed via {$paymentMethod}",
                ]);
                
                // Also mark commission and gateway fees as processed
                FinancialTransaction::where('order_id', $order->id)
                    ->whereIn('transaction_type', [
                        FinancialTransaction::TYPE_COMMISSION,
                        FinancialTransaction::TYPE_GATEWAY_FEE,
                    ])
                    ->update([
                        'status' => FinancialTransaction::STATUS_PROCESSED,
                        'processed_at' => now(),
                    ]);
                    
                Log::info('Financial transactions marked as processed for full payment', [
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod,
                    'amount_paid' => $amountPaid,
                ]);
            } elseif ($amountPaid > 0) {
                // Partial payment
                $revenueTransaction->update([
                    'status' => FinancialTransaction::STATUS_PENDING,
                    'description' => "Order #{$order->order_number} - Partial payment ({$paymentPercentage}%) via {$paymentMethod}",
                    'metadata' => array_merge($revenueTransaction->metadata ?? [], [
                        'partial_payment_amount' => $amountPaid,
                        'partial_payment_percentage' => $paymentPercentage . '%',
                        'payment_method' => $paymentMethod,
                    ]),
                ]);
                
                Log::info('Financial transactions updated for partial payment', [
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod,
                    'amount_paid' => $amountPaid,
                    'percentage' => $paymentPercentage . '%',
                ]);
            } else {
                // Payment initiated but not received yet (like Stripe)
                $revenueTransaction->update([
                    'description' => "Order #{$order->order_number} - Payment initiated via {$paymentMethod}",
                ]);
            }
        }
    }
    
    /** -------------------------------------------------------------
     *   CREATE FINANCIAL TRANSACTIONS FOR EXISTING ORDER
     *   (For orders created before the finance module was added)
     * ------------------------------------------------------------- */
    private function createFinancialTransactionsForExistingOrder($order)
    {
        $vendor = $order->vendor;
        $currency = $vendor->currency->code ?? 'USD';
        
        // Calculate all fees
        $breakdown = $this->financeCalculator->calculateOrderBreakdown($order);
        
        // Create transactions if they don't exist
        $existingTransactions = FinancialTransaction::where('order_id', $order->id)->count();
        
        if ($existingTransactions === 0) {
            // 1. Order Revenue Transaction
            FinancialTransaction::create([
                'order_id' => $order->id,
                'vendor_id' => $vendor->id,
                'transaction_type' => FinancialTransaction::TYPE_ORDER,
                'amount' => $breakdown['gross_amount'],
                'currency' => $currency,
                'description' => "Order #{$order->order_number} - Retroactively added",
                'reference_number' => $order->order_number . '-RETRO-REV',
                'gateway_fee' => 0,
                'commission_fee' => 0,
                'wire_fee' => 0,
                'net_amount' => $breakdown['gross_amount'],
                'status' => $order->payment_status === 'paid' 
                    ? FinancialTransaction::STATUS_PROCESSED 
                    : FinancialTransaction::STATUS_PENDING,
                'processed_at' => $order->payment_status === 'paid' ? now() : null,
                'metadata' => [
                    'order_number' => $order->order_number,
                    'retroactively_added' => true,
                    'added_date' => now()->toDateString(),
                ],
            ]);
            
            // 2. Commission Fee Transaction
            if ($breakdown['commission'] > 0) {
                FinancialTransaction::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendor->id,
                    'transaction_type' => FinancialTransaction::TYPE_COMMISSION,
                    'amount' => $breakdown['commission'] * -1,
                    'currency' => $currency,
                    'description' => "Commission for Order #{$order->order_number} - Retroactively added",
                    'reference_number' => $order->order_number . '-RETRO-COMM',
                    'gateway_fee' => 0,
                    'commission_fee' => $breakdown['commission'],
                    'wire_fee' => 0,
                    'net_amount' => $breakdown['commission'] * -1,
                    'status' => $order->payment_status === 'paid' 
                        ? FinancialTransaction::STATUS_PROCESSED 
                        : FinancialTransaction::STATUS_PENDING,
                    'processed_at' => $order->payment_status === 'paid' ? now() : null,
                    'metadata' => [
                        'order_number' => $order->order_number,
                        'commission_rate' => $breakdown['breakdown']['commission_percentage'] . '%',
                        'retroactively_added' => true,
                    ],
                ]);
            }
            
            // 3. Gateway Fee Transaction
            if ($breakdown['gateway_fee'] > 0) {
                FinancialTransaction::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendor->id,
                    'transaction_type' => FinancialTransaction::TYPE_GATEWAY_FEE,
                    'amount' => $breakdown['gateway_fee'] * -1,
                    'currency' => $currency,
                    'description' => "Gateway fee for Order #{$order->order_number} - Retroactively added",
                    'reference_number' => $order->order_number . '-RETRO-GATE',
                    'gateway_fee' => $breakdown['gateway_fee'],
                    'commission_fee' => 0,
                    'wire_fee' => 0,
                    'net_amount' => $breakdown['gateway_fee'] * -1,
                    'status' => $order->payment_status === 'paid' 
                        ? FinancialTransaction::STATUS_PROCESSED 
                        : FinancialTransaction::STATUS_PENDING,
                    'processed_at' => $order->payment_status === 'paid' ? now() : null,
                    'metadata' => [
                        'order_number' => $order->order_number,
                        'payment_method' => $order->payment_method,
                        'retroactively_added' => true,
                    ],
                ]);
            }
            
            Log::info('Retroactively created financial transactions for existing order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.paiement-modal');
    }
}
