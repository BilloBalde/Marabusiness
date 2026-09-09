<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZonePriceTier extends Model
{
    protected $fillable = [
        'delivery_zone_price_id',
        'min_qty',
        'max_qty',
        'price_usd',
    ];

    protected $casts = [
        'min_qty'   => 'integer',
        'max_qty'   => 'integer',
        'price_usd' => 'decimal:4',
    ];

    public function deliveryZonePrice(): BelongsTo
    {
        return $this->belongsTo(DeliveryZonePrice::class);
    }
}
