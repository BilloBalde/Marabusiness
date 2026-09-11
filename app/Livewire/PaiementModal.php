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

    /**
     * The receipt is required for Orange Money and optional for cash on delivery.
     * Money really moves on an Orange Money transfer, and the buyer has a
     * confirmation screen to show for it; there is nothing to photograph when you
     * hand notes to a courier, and the vendor confirms that one on receipt anyway.
     */
    protected function rules(): array
    {
        return [
            'payment_method' => 'required|string',
            'amount'         => 'nullable|numeric|min:1',
            'image'          => in_array($this->payment_method, Paiement::METHODS_REQUIRING_PROOF, true)
                ? 'required|image|max:2048'
                : 'nullable|image|max:2048',
        ];
    }

    protected function messages(): array
    {
        return [
            'image.required' => 'Joignez la capture de votre transfert Orange Money : sans elle, le vendeur ne peut pas vérifier le paiement.',
            'image.image'    => 'Le justificatif doit être une image (capture d\'écran ou photo).',
            'image.max'      => 'Le justificatif ne doit pas dépasser 2 Mo.',
        ];
    }

    public function save()
    {
        $this->validate();

        $order = $this->order;

        // A price still under discussion is not a price to collect. Nothing here
        // looked at the order's status — only at what was left to pay — so an
        // order in negotiation was payable at whatever provisional figure the
        // basket happened to carry.
        if ($order->isNegotiating()) {
            session()->flash('error', "Le prix de cette commande est en cours de négociation. Acceptez le prix proposé avant de régler.");

            return;
        }

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

            Stripe::setApiKey(config('services.stripe.secret'));

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

        $vendor   = $order->vendor;
        $currency = $vendor->currency->code ?? 'USD';

        // What is genuinely still owed, derived from the payments themselves rather
        // than the total_remaining column, which is 0 both on a settled order and on
        // older orders where it was never filled in. Declarations awaiting the
        // vendor's confirmation count here too, so a buyer cannot declare the same
        // amount twice while the first one is still being checked.
        $alreadyPaid  = (float) $order->paiements()->sum('amount');
        $remainingDue = max(0, (float) $order->grand_total - $alreadyPaid);

        if ($remainingDue <= 0) {
            $this->addError('amount', $order->declaredAwaitingConfirmation() > 0
                ? 'Un paiement est déjà déclaré pour cette commande et attend la validation du vendeur.'
                : 'Cette commande est déjà réglée.');

            return;
        }

        // paiements.amount is NOT NULL, and the form let this through empty: choosing
        // "cash on delivery" without typing an amount — the natural thing to do, since
        // you pay the courier — crashed on a database constraint. An unstated amount
        // means the buyer is settling the balance. Capped so a second submission cannot
        // push total_paid past the order total.
        $amount = min((float) ($this->amount ?: $remainingDue), $remainingDue);

        // Compared in the order's own currency. The previous version divided by
        // $vendor->rate_to_usd — a column that lives on currencies, not vendors, so it
        // was always null and the "USD" comparison was never one.
        $settles = ($alreadyPaid + $amount) >= (float) $order->grand_total;

        // Declared, not received. This is the buyer saying they have paid; the order's
        // balance only moves once the vendor confirms the money arrived. Marking it
        // paid here is what let a buyer settle their own cash-on-delivery order before
        // the courier had collected anything.
        Paiement::create([
            'order_id'       => $order->id,
            'amount'         => $amount,
            'image'          => $imagePath,
            'payment_method' => $this->payment_method,
            'currency'       => $currency,
            'payment_status' => $settles ? 'paid' : 'partial',
            'transaction_id' => Order::generateTransactionNumber(),
            'confirmed_at'   => null,
            'confirmed_by'   => null,
        ]);

        // Recomputes from confirmed money only, so this declaration leaves the order
        // where it was until the vendor acts on it.
        $order->update(['payment_method' => $this->payment_method]);
        $order->syncPaymentTotals();

        $this->updateOrderFinancialTransactionsForPayment($order, $this->payment_method, $amount, 'Payment declared by buyer');

        $this->reset(['amount', 'image', 'payment_method', 'showModal']);
        $this->dispatch('payment-made');
        $this->dispatch('close-modal');
        $this->dispatch('payment-declared');
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
