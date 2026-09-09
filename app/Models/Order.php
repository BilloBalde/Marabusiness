<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\FinanceCalculator;

class Order extends Model
{
    use HasFactory;

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
        'lengopay_pay_id',
        'lengopay_payment_url',
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

    /**
     * The reference stamped on a payment.
     *
     * This used to look up existing references with the prefix "TRANS" but return one
     * prefixed "INV": the search therefore never matched anything it had produced, the
     * increment stayed at 1, and every payment of a given month was handed the exact
     * same reference. 87 payments ended up sharing 23 references, one of them used 19
     * times — a payment could not be identified by its reference at all.
     *
     * "TRANS" is the intended prefix: it is what this method already searched for, what
     * the two Filament screens produce, and it keeps payment references distinct from
     * order numbers, which use "INV" and were otherwise indistinguishable.
     */
    public static function generateTransactionNumber(): string
    {
        $currentYearMonth = now()->format('Ym');

        $latest = DB::table('paiements')
            ->where('transaction_id', 'like', "TRANS{$currentYearMonth}%")
            ->orderByDesc('transaction_id')
            ->first();

        $increment = $latest
            ? ((int) substr($latest->transaction_id, -4)) + 1
            : 1;

        // Reading the highest reference and adding one races with a concurrent payment,
        // and cannot resolve a duplicate already present. Step forward until the
        // candidate is genuinely free.
        do {
            $candidate = "TRANS{$currentYearMonth}" . str_pad((string) $increment, 4, '0', STR_PAD_LEFT);
            $increment++;
        } while (DB::table('paiements')->where('transaction_id', $candidate)->exists());

        return $candidate;
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Payments the shop has actually received, as opposed to ones a buyer has merely
     * declared and nobody has confirmed yet.
     */
    public function confirmedPaiements()
    {
        return $this->hasMany(Paiement::class)->whereNotNull('confirmed_at');
    }

    /**
     * Recomputes the balance from confirmed money alone.
     *
     * The same three lines were written out at a dozen call sites, each free to forget
     * a rule — which is how payment_status came to be left untouched while total_paid
     * was updated, so a settled order kept reading "En attente de paiement". One
     * method now owns it.
     */
    public function syncPaymentTotals(): void
    {
        $paid = (float) $this->confirmedPaiements()->sum('amount');
        $remaining = max(0, (float) $this->grand_total - $paid);

        $status = match (true) {
            $paid <= 0    => 'pending',
            $remaining> 0 => 'partial',
            default       => 'paid',
        };

        $this->update([
            'total_paid'      => $paid,
            'total_remaining' => $remaining,
            'payment_status'  => $status,
        ]);
    }

    /**
     * What a buyer has declared but no one has confirmed. Shown to the vendor as
     * something to act on, and to the buyer so they know it is pending, not lost.
     */
    public function declaredAwaitingConfirmation(): float
    {
        return (float) $this->paiements()->whereNull('confirmed_at')->sum('amount');
    }

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
