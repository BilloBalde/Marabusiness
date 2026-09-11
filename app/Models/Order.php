<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'negotiation_status',
        'negotiated_total',
        'negotiated_expires_at',
        'pre_negotiation_total',
        // Both cancellation paths — OrderDetailPage::cancelOrder() on the web and
        // OrderController::cancel() on the API — have always passed these to
        // update(), and mass assignment has always dropped them: all nine
        // cancelled orders in the database carry neither a date nor a reason.
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'negotiated_expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /** The order is waiting on a price, in one form or another. */
    public const STATUS_NEGOTIATING = 'negotiating';

    /** The buyer has asked; nobody has named a price yet. */
    public const NEGOTIATION_OPEN = 'open';

    /** The vendor has named a price and it is waiting on the buyer. */
    public const NEGOTIATION_PRICED = 'priced';

    /** The buyer took the price. The order is an ordinary payable order again. */
    public const NEGOTIATION_AGREED = 'agreed';

    /**
     * Nothing about this order may be paid yet.
     *
     * Both payment paths only ever checked payment_status === 'paid', so without
     * this an order under discussion would be payable at whatever provisional
     * figure the basket happened to carry.
     */
    public function isNegotiating(): bool
    {
        return $this->status === self::STATUS_NEGOTIATING;
    }

    /** A price is on the table and has not run out. */
    public function hasLiveOffer(): bool
    {
        return $this->negotiation_status === self::NEGOTIATION_PRICED
            && $this->negotiated_total !== null
            && ($this->negotiated_expires_at === null || $this->negotiated_expires_at->isFuture());
    }

    /** A price was named and the buyer let it lapse. */
    public function offerHasExpired(): bool
    {
        return $this->negotiation_status === self::NEGOTIATION_PRICED
            && $this->negotiated_expires_at !== null
            && $this->negotiated_expires_at->isPast();
    }

    /**
     * Negotiations that are waiting on the vendor to name a price.
     *
     * Two situations, not one: a request nobody has answered, and a price the
     * buyer let lapse. The second is easy to miss because negotiations:expire
     * would normally move it back to 'open' — but nothing runs the scheduler on
     * this deployment (Render binds the sqlite disk to the single web service,
     * as routes/console.php records), so a lapsed order stays at 'priced'
     * indefinitely. Counting only 'open' would leave it invisible to the vendor
     * while the buyer can no longer accept it: a deadlock with no signal.
     *
     * Written once here because the navigation badge and the "Négociations" tab
     * both need it, and two copies of a rule are how the two
     * convertUsdToVendorCurrency and the five image-URL helpers came about.
     */
    public function scopeAwaitingVendorPrice(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_NEGOTIATING)
            ->where(function (Builder $inner) {
                $inner->where('negotiation_status', self::NEGOTIATION_OPEN)
                    ->orWhere(function (Builder $expired) {
                        $expired->where('negotiation_status', self::NEGOTIATION_PRICED)
                            ->whereNotNull('negotiated_expires_at')
                            ->where('negotiated_expires_at', '<', now());
                    });
            });
    }

    /** The negotiation thread, when this order came from one. */
    public function negotiation()
    {
        return $this->hasOne(BulkRfq::class);
    }

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
            // Une commande en négociation n'a pas d'écritures, et c'est voulu :
            // aucune somme n'est convenue, il n'y a rien à porter aux comptes.
            // OrderNegotiation::accept() les crée au moment où le client accepte
            // le prix. Sans cette condition, chaque discussion ouverte laissait
            // un avertissement dans le journal — un signal qui ne signale rien
            // finit par masquer ceux qui comptent.
            if ($order->status === self::STATUS_NEGOTIATING) {
                return;
            }

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
     *
     * Each confirmed payment is converted into the order's OWN frozen rate_to_usd
     * before being summed — not just added as a raw number. `paiements.currency` is
     * a real, independent column: nothing forces it to match the order's currency,
     * and on this database it sometimes does not (an order priced at 54.03 in its
     * own currency carrying a single confirmed payment of 3,000,000 GNF is real,
     * live data, not a hypothetical). Summing raw amounts across two different
     * currencies produced exactly what that pairing shows — total_paid dwarfing
     * grand_total by a factor of tens of thousands, and the order reading "paid"
     * regardless of what actually arrived.
     */
    public function syncPaymentTotals(): void
    {
        $paid = (float) $this->confirmedPaiements()
            ->get(['amount', 'currency'])
            ->sum(fn (Paiement $payment) => \App\Support\Money::convert(
                (float) $payment->amount,
                $this->rateToUsdFor($payment->currency),
                (float) $this->rate_to_usd,
            ));

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
     * The rate to convert a payment's own currency into USD, for syncPaymentTotals().
     *
     * The common case — a payment in the order's own currency — never queries
     * `currencies`: it reuses the rate already frozen on the order, which is also
     * more correct than a fresh lookup would be, since a vendor's currency and its
     * rate can change after the order was placed. A lookup only happens for a
     * payment recorded in some OTHER currency, and an unresolvable code (renamed,
     * deleted, or simply typo'd on a manual entry) returns null — Money::convert()
     * then passes the amount through unconverted, the same fallback it already
     * uses for a zero or missing rate, rather than silently dropping the payment
     * from the balance.
     *
     * Compared case-insensitively: currency codes are meant to be stored
     * uppercase (every Currency row is), but this database has real Paiement
     * rows carrying 'gnf' next to others carrying 'GNF' for the very same
     * order — manual entry that never went through a code that normalises it.
     * An exact-case comparison would treat those as two different currencies
     * and either skip the free fast path or fail the lookup below for a
     * payment that is, in fact, already in the order's own currency.
     */
    private function rateToUsdFor(?string $paymentCurrency): ?float
    {
        $orderCurrency = $this->vendor?->currency?->code;

        if ($paymentCurrency === null || strcasecmp($paymentCurrency, (string) $orderCurrency) === 0) {
            return $this->rate_to_usd;
        }

        return Currency::whereRaw('UPPER(code) = ?', [strtoupper($paymentCurrency)])->value('rate_to_usd');
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
