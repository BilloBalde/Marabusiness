<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorShippingRateTier extends Model
{
    protected $fillable = [
        'vendor_shipping_rate_id',
        'min_qty',
        'max_qty',
        'amount',
    ];

    protected $casts = [
        'min_qty' => 'integer',
        'max_qty' => 'integer',
        'amount'  => 'decimal:2',
    ];

    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(VendorShippingRate::class, 'vendor_shipping_rate_id');
    }
}
