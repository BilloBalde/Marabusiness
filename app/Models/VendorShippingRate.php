<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorShippingRate extends Model
{
    protected $fillable = [
        'vendor_id',
        'locality_id',
        'amount',
        'delivery_days',
        'is_active',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'delivery_days' => 'integer',
        'is_active'     => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(VendorShippingRateTier::class);
    }

    /**
     * Delivery price for a given number of items, in the vendor's currency.
     *
     * A matching quantity bracket wins; otherwise the flat amount set when the vendor
     * added the locality applies. Same resolution order as
     * VendorProduct::getWholesalePriceForQty(): widest matching bracket by min_qty,
     * with a null max_qty meaning "and above".
     */
    public function amountForQuantity(int $quantity): float
    {
        $tier = $this->tiers()
            ->where('min_qty', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->whereNull('max_qty')->orWhere('max_qty', '>=', $quantity);
            })
            ->orderByDesc('min_qty')
            ->first();

        return (float) ($tier->amount ?? $this->amount);
    }
}
