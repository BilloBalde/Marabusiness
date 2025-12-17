<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Purchase extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'vendor_id',
        'supplier_id',
        'currency_id',
        'reference',
        'status',
        'total_cost',
        'total_quantity',
        'notes',
        'expected_at',
        'ordered_at',
        'received_at',
        'inventory_applied_at',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'total_quantity' => 'integer',
        'expected_at' => 'datetime',
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
        'inventory_applied_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (Purchase $purchase) {
            if ($purchase->isDirty('status') && $purchase->status === self::STATUS_RECEIVED) {
                $purchase->received_at = $purchase->received_at ?? now();
            }
        });

        static::updated(function (Purchase $purchase) {
            if (
                $purchase->status === self::STATUS_RECEIVED &&
                $purchase->inventory_applied_at === null
            ) {
                $purchase->applyInventoryAdjustments();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function applyInventoryAdjustments(): void
    {
        if ($this->inventory_applied_at) {
            return;
        }

        DB::transaction(function () {
            $this->items()->with('vendorProduct')->lockForUpdate()->get()->each(function (PurchaseItem $item) {
                if ($item->vendorProduct) {
                    $item->vendorProduct->increment('stock', $item->quantity);
                }
            });

            $this->forceFill([
                'inventory_applied_at' => now(),
            ])->saveQuietly();
        });
    }

    /**
     * 🔥 Recalculate total_quantity & total_cost from purchase_items table.
     */
    public function refreshTotals(): void
    {
        $summary = $this->items()
            ->selectRaw('COALESCE(SUM(quantity), 0) as qty, COALESCE(SUM(total_cost_items), 0) as total')
            ->first();

        $this->forceFill([
            'total_quantity' => (int) ($summary->qty ?? 0),
            'total_cost'     => (float) ($summary->total ?? 0),
        ])->saveQuietly();
    }

    public static function generateReference(): string
    {
        return 'PO-' . Carbon::now()->format('Ym') . Str::upper(Str::ulid());
    }
}
