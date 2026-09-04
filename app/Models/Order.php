<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\FinanceCalculator;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'vendor_id',
        'order_number',
        'status',
        'grand_total',
        'payment_method',
        'payment_status',
        'shipping_amount',
        'shipping_carrier',
        'notes',
        'total_paid',
        'total_remaining',
        'stripe_session_id',
        'grand_total_usd',
        'shipping_amount_usd',
        'rate_to_usd',
    ];

    protected static function booted()
    {
        static::updated(function ($order) {
            // When order is marked as delivered, create financial transactions if not exists
            if ($order->wasChanged('status') && $order->status === 'delivered') {
                $financeCalculator = new FinanceCalculator();
                $existingTransactions = FinancialTransaction::where('order_id', $order->id)->count();
                
                if ($existingTransactions === 0) {
                    // Create financial transactions for delivered order
                    $financeCalculator->createFinancialTransactionsForOrder($order);
                }
            }
            
            // When payment status changes to paid, update financial transactions
            if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
                FinancialTransaction::where('order_id', $order->id)
                    ->update([
                        'status' => FinancialTransaction::STATUS_PROCESSED,
                        'processed_at' => now(),
                    ]);
            }
        });
        
        static::created(function ($order) {
            // Create initial financial transactions for new order
            // (This will be handled by CheckoutPage, but added here as backup)
            $existingTransactions = FinancialTransaction::where('order_id', $order->id)->count();
            
            if ($existingTransactions === 0) {
                // These will be created by CheckoutPage, so we log if they're missing
                \Log::warning('Order created without financial transactions', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function latestShipment()
    {
        return $this->hasOne(Shipment::class)->latestOfMany();
    }

    public static function generateOrderNumber()
    {
        $currentYearMonth = now()->format('Ym'); // Get the current YearMonth (e.g., "202504")

        // Get the latest order number for the current year and month
        $latestOrder = DB::table('orders')
            ->where('order_number', 'like', "INV{$currentYearMonth}%")
            ->orderByDesc('order_number')
            ->first();

        // Get the latest increment number
        $increment = 1;
        if ($latestOrder) {
            $lastIncrement = (int)substr($latestOrder->order_number, -4); // Extract last 4 digits of the order number
            $increment = $lastIncrement + 1;
        }

        // Format the increment as a 4-digit number
        $formattedIncrement = str_pad($increment, 4, '0', STR_PAD_LEFT);

        // Return the full order number
        return "INV{$currentYearMonth}{$formattedIncrement}";
    }

    public static function generateTransactionNumber()
    {
        $currentYearMonth = now()->format('Ym'); // Get the current YearMonth (e.g., "202504")

        // Get the latest order number for the current year and month
        $latestPaiement = DB::table('paiements')
            ->where('transaction_id', 'like', "TRANS{$currentYearMonth}%")
            ->orderByDesc('transaction_id')
            ->first();

        // Get the latest increment number
        $increment = 1;
        if ($latestPaiement) {
            $lastIncrement = (int)substr($latestPaiement->transaction_id, -4); // Extract last 4 digits of the order number
            $increment = $lastIncrement + 1;
        }

        // Format the increment as a 4-digit number
        $formattedIncrement = str_pad($increment, 4, '0', STR_PAD_LEFT);

        // Return the full order number
        return "INV{$currentYearMonth}{$formattedIncrement}";
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
