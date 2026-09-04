<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Paiement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Title;
use Livewire\Component;
use Stripe\Stripe;
use Stripe\Checkout\Session;

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

        // Retrieve ALL orders created IN THIS SESSION
        $this->orders = Order::with(['address', 'items', 'items.product', 'vendor', 'latestShipment'])
            ->where('user_id', $userId)
            ->whereDate('created_at', now()->toDateString()) // same day
            ->latest()
            ->get();
    }

    protected function verifyStripePayment()
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            
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
                        
                        $localAmount = $orderCurrency === 'USD' 
                            ? $totalAmount 
                            : $totalAmount / $orderRate;
                        
                        // Create payment record
                        Paiement::create([
                            'order_id' => $order->id,
                            'amount' => $localAmount,
                            'payment_method' => 'stripe',
                            'currency' => $orderCurrency,
                            'payment_status' => 'paid',
                            'transaction_id' => 'stripe_' . $session->id,
                        ]);
                        
                        // Update order totals
                        $totalPaid = $order->paiements()->sum('amount');
                        $remaining = max(0, $order->grand_total - $totalPaid);
                        
                        $paymentStatus = $remaining <= 0 ? 'paid' : 'partial';
                        
                        $order->update([
                            'total_paid' => $totalPaid,
                            'total_remaining' => $remaining,
                            'payment_status' => $paymentStatus,
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

    public function render()
    {
        return view('livewire.success-page', [
            'orders' => $this->orders,
            'stripe_payment_completed' => $this->stripe_payment_completed,
            'session_id' => $this->session_id,
        ]);
    }
}