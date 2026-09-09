<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The one shared delivery price for a Locality, visible to every vendor. Replaces
 * VendorShippingRate, which priced a locality privately per vendor and was never
 * adopted (0 rows) before being dropped.
 *
 * Stored in USD regardless of who set it or which vendor is quoting: a single shared
 * number only stays meaningful across vendors billing in GNF, CNY or USD if it lives
 * in one common unit. See VendorShippingRate::amountForQuantity() for the equivalent
 * per-vendor tier resolution this mirrors.
 */
class DeliveryZonePrice extends Model
{
    protected $fillable = [
        'locality_id',
        'price_usd',
        'delivery_days',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price_usd'     => 'decimal:4',
        'delivery_days' => 'integer',
        'is_active'     => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(DeliveryZonePriceTier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Price in USD for a given quantity: a matching bracket wins, otherwise the flat
     * price. Same resolution order as VendorProduct::getWholesalePriceForQty() — widest
     * matching bracket by min_qty, null max_qty meaning "and above".
     */
    public function usdForQuantity(int $quantity): float
    {
        $tier = $this->tiers()
            ->where('min_qty', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->whereNull('max_qty')->orWhere('max_qty', '>=', $quantity);
            })
            ->orderByDesc('min_qty')
            ->first();

        return (float) ($tier->price_usd ?? $this->price_usd);
    }
}
