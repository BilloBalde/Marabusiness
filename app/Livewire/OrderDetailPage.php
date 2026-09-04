<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Refund;
use App\Models\FinancialTransaction;
use App\Models\Paiement;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use App\Services\LengoPayService;

#[Title('Order Detail - MARA BUSINESS')]
class OrderDetailPage extends Component
{
    public $order;
    public $showCancelModal = false;
    public $cancellationReason = '';

    protected $rules = [
        'cancellationReason' => 'required|string|min:5|max:500',
    ];

    protected $messages = [
        'cancellationReason.required' => 'Veuillez indiquer la raison de l\'annulation.',
        'cancellationReason.min' => 'La raison doit contenir au moins 5 caractères.',
        'cancellationReason.max' => 'La raison ne peut pas dépasser 500 caractères.',
    ];

    public function mount($order_id)
    {
        $this->order = Order::with(['address', 'items.product', 'vendor.currency', 'latestShipment'])
            ->findOrFail($order_id);
    }

    public function syncTracking(): void
    {
        $shipment = $this->order->latestShipment;

        if (! $shipment) {
            return;
        }

        app(\App\Services\Shipping\CarrierTrackingService::class)->sync($shipment);
        $this->order->refresh();
    }

    /**
     * Open the cancellation modal
     */
    public function openCancelModal()
    {
        $this->resetErrorBag();
        $this->cancellationReason = '';
        $this->showCancelModal = true;
    }

    /**
     * Close the cancellation modal
     */
    public function closeCancelModal()
    {
        $this->showCancelModal = false;
        $this->cancellationReason = '';
    }

    /**
     * Cancel the order with reason
     */
    public function cancelOrder()
    {
        $this->validate();

        // Verify order belongs to current user and is cancellable
        if ($this->order->user_id !== Auth::id()) {
            session()->flash('error', 'Vous n\'êtes pas autorisé à annuler cette commande.');
            return redirect()->route('my-orders');
        }

        if ($this->order->status !== 'new') {
            session()->flash('error', 'Cette commande ne peut plus être annulée.');
            return redirect()->back();
        }

        DB::beginTransaction();

        try {
            // 1. Update order status
            $this->order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $this->cancellationReason,
            ]);

            // 2. Restore stock for all items
            foreach ($this->order->items as $item) {
                $this->restoreStock($item);
            }

            // 3. Reverse financial transactions
            $this->reverseFinancialTransactions();

            // 4. Handle refund based on payment method
            if (in_array($this->order->payment_status, ['paid', 'partial'])) {
                $this->handleRefund();
            }

            // 5. Update payment status
            $this->order->update([
                'payment_status' => 'refunded',
            ]);

            DB::commit();

            // Close modal and refresh
            $this->closeCancelModal();
            $this->order->refresh();

            session()->flash('success', 'Commande annulée avec succès.');
            return redirect()->route('my-orders');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order cancellation failed: ' . $e->getMessage(), [
                'order_id' => $this->order->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            session()->flash('error', 'Une erreur est survenue lors de l\'annulation.');
            return redirect()->back();
        }
    }

    /**
     * Restore stock for a single order item
     */
    private function restoreStock($item)
    {
        // Check if this is a variation
        if ($item->variation_json && isset($item->variation_json['variation_id'])) {
            $variation = \App\Models\VendorProductVariation::where('vendor_product_id', $item->vendor_product_id)
                ->where('id', $item->variation_json['variation_id'])
                ->first();
            
            if ($variation) {
                $variation->stock += $item->quantity;
                $variation->save();
                
                // Update parent vendor product stock
                $totalVariationStock = \App\Models\VendorProductVariation::where('vendor_product_id', $item->vendor_product_id)
                    ->sum('stock');
                
                $vp = \App\Models\VendorProduct::find($item->vendor_product_id);
                if ($vp) {
                    $vp->stock = $totalVariationStock;
                    $vp->save();
                }
            }
        } else {
            // Regular product
            $vp = \App\Models\VendorProduct::where('vendor_id', $this->order->vendor_id)
                ->where('product_id', $item->product_id)
                ->first();
            
            if ($vp) {
                $vp->stock += $item->quantity;
                $vp->save();
            }
        }
    }

    /**
     * Mark financial transactions as cancelled
     */
    private function reverseFinancialTransactions()
    {
        FinancialTransaction::where('order_id', $this->order->id)
            ->update([
                'status' => 'cancelled',
                'processed_at' => now(),
                'description' => DB::raw("CONCAT(description, ' - CANCELLED')"),
            ]);
    }

    /**
     * Handle refund based on payment method
     */
    private function handleRefund()
    {
        switch ($this->order->payment_method) {
            case 'stripe':
                $this->refundStripe();
                break;
            case 'lengopay':
                $this->refundLengoPay();
                break;
            case 'om':
                $this->refundOrangeMoney();
                break;
            case 'cod':
            case 'cash':
                // Cash/COD orders - no actual refund needed, just update status
                Log::info('Cash/COD order cancelled - no refund processed', [
                    'order_id' => $this->order->id,
                ]);
                break;
        }
    }

    /**
     * Process Stripe refund
     */
    private function refundStripe()
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            
            // Get the Stripe payment for this order
            $payment = Paiement::where('order_id', $this->order->id)
                ->where('payment_method', 'stripe')
                ->first();
            
            if ($payment && $payment->transaction_id) {
                $refund = \Stripe\Refund::create([
                    'payment_intent' => $payment->transaction_id,
                    'amount' => intval(round($this->order->grand_total_usd * 100)),
                    'reason' => 'requested_by_customer',
                ]);
                
                // Create refund record
                \App\Models\Refund::create([
                    'order_id' => $this->order->id,
                    'payment_id' => $payment->id,
                    'amount' => $this->order->grand_total,
                    'amount_usd' => $this->order->grand_total_usd,
                    'refund_id' => $refund->id,
                    'reason' => $this->cancellationReason,
                    'status' => 'completed',
                ]);
                
                Log::info('Stripe refund processed', [
                    'order_id' => $this->order->id,
                    'refund_id' => $refund->id,
                    'amount' => $this->order->grand_total_usd,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Stripe refund failed: ' . $e->getMessage(), [
                'order_id' => $this->order->id,
            ]);
            
            // Still mark the order as cancelled but note the refund issue
            \App\Models\Refund::create([
                'order_id' => $this->order->id,
                'amount' => $this->order->grand_total,
                'amount_usd' => $this->order->grand_total_usd,
                'reason' => $this->cancellationReason,
                'status' => 'failed',
                'notes' => 'Stripe refund failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Process LengoPay refund
     */
    private function refundLengoPay()
    {
        try {
            $lengo = app(LengoPayService::class);
            
            // Get the LengoPay payment for this order
            $payment = Paiement::where('order_id', $this->order->id)
                ->where('payment_method', 'lengopay')
                ->first();
            
            if ($payment && $payment->transaction_id) {
                // Call LengoPay refund API
                $refundResult = $lengo->refundPayment(
                    $payment->transaction_id, 
                    $this->order->grand_total,
                    $this->cancellationReason
                );
                
                if ($refundResult['success']) {
                    Refund::create([
                        'order_id' => $this->order->id,
                        'payment_id' => $payment->id,
                        'amount' => $this->order->grand_total,
                        'amount_usd' => $this->order->grand_total_usd,
                        'refund_id' => $refundResult['refund_id'],
                        'reason' => $this->cancellationReason,
                        'status' => 'completed',
                    ]);
                    
                    Log::info('LengoPay refund processed', [
                        'order_id' => $this->order->id,
                        'refund_id' => $refundResult['refund_id'],
                        'amount' => $this->order->grand_total,
                    ]);
                } else {
                    throw new \Exception($refundResult['message'] ?? 'LengoPay refund failed');
                }
            } else {
                // No payment record found, create pending refund
                Refund::create([
                    'order_id' => $this->order->id,
                    'amount' => $this->order->grand_total,
                    'amount_usd' => $this->order->grand_total_usd,
                    'reason' => $this->cancellationReason,
                    'status' => 'pending',
                    'notes' => 'No payment record found for LengoPay - manual processing required',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('LengoPay refund failed: ' . $e->getMessage(), [
                'order_id' => $this->order->id,
            ]);
            
            // Create pending refund record for manual processing
            Refund::create([
                'order_id' => $this->order->id,
                'amount' => $this->order->grand_total,
                'amount_usd' => $this->order->grand_total_usd,
                'reason' => $this->cancellationReason,
                'status' => 'pending',
                'notes' => 'LengoPay refund failed: ' . $e->getMessage() . ' - Manual processing required',
            ]);
        }
    }

    /**
     * Process Orange Money refund
     */
    private function refundOrangeMoney()
    {
        // Create refund record for manual processing
        \App\Models\Refund::create([
            'order_id' => $this->order->id,
            'amount' => $this->order->grand_total,
            'amount_usd' => $this->order->grand_total_usd,
            'reason' => $this->cancellationReason,
            'status' => 'pending',
            'notes' => 'Orange Money refund - needs manual processing. Contact support to process this refund.',
        ]);
        
        Log::info('Orange Money refund created - pending manual processing', [
            'order_id' => $this->order->id,
        ]);
    }
    public function render()
    {
        //dd($this->order);
        return view('livewire.order-detail-page', [
            'order' => $this->order,
            'address' => $this->order->address ?? 'no address',
            'order_items' => $this->order->items,
        ]);
    }
}
