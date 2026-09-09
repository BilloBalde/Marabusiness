<?php

namespace App\Models;

use App\Mail\PaymentDeclared;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Paiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'payment_status',
        'currency',
        'amount',
        'image',
        'transaction_id',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    /**
     * Payment methods where the money moves outside the platform, so someone has to
     * say it arrived: cash handed to a courier, an Orange Money transfer. Card
     * payments are confirmed by the gateway itself.
     */
    public const OFFLINE_METHODS = ['cod', 'cash', 'om'];

    /**
     * Methods where the buyer is expected to attach a receipt. Cash on delivery is
     * excluded on purpose: there is nothing to screenshot when you hand notes to a
     * courier, and the vendor confirms that one on receipt anyway.
     */
    public const METHODS_REQUIRING_PROOF = ['om'];

    /**
     * A declaration is announced from here rather than from each screen that records
     * one: buyers can declare a payment from the web modal, the mobile API and two
     * checkout paths, and a fifth would eventually be added without the notification.
     *
     * Sent after the surrounding transaction commits — several of those callers wrap
     * order creation in one — and never allowed to break the payment it announces.
     */
    protected static function booted(): void
    {
        static::created(function (Paiement $paiement) {
            if ($paiement->isConfirmed()) {
                return; // gateway-verified or staff-entered: nothing to confirm
            }

            DB::afterCommit(function () use ($paiement) {
                $email = $paiement->order?->vendor?->user?->email;

                if (! $email) {
                    Log::warning('Paiement déclaré : la boutique n\'a pas d\'e-mail, vendeur non prévenu.', [
                        'paiement_id' => $paiement->id,
                    ]);

                    return;
                }

                try {
                    Mail::to($email)->send(new PaymentDeclared($paiement));
                } catch (\Throwable $e) {
                    Log::error('Échec de la notification de paiement déclaré au vendeur.', [
                        'paiement_id' => $paiement->id,
                        'error'       => $e->getMessage(),
                    ]);
                }
            });
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Money the shop has actually received. Only these count towards an order's
     * balance — a buyer's unconfirmed declaration does not.
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at');
    }

    public function scopeAwaitingConfirmation(Builder $query): Builder
    {
        return $query->whereNull('confirmed_at');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function requiresProof(): bool
    {
        return in_array($this->payment_method, self::METHODS_REQUIRING_PROOF, true);
    }

    /**
     * Records that the money arrived, and brings the order's balance up to date.
     */
    public function confirm(?User $by = null): void
    {
        if ($this->isConfirmed()) {
            return;
        }

        $this->update([
            'confirmed_at' => now(),
            'confirmed_by' => $by?->id,
        ]);

        $this->order?->syncPaymentTotals();
    }
}
