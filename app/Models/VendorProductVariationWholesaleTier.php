<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorProductVariationWholesaleTier extends Model
{
    protected $fillable = ['vendor_product_variation_id', 'min_qty', 'max_qty', 'price'];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function variation()
    {
        return $this->belongsTo(VendorProductVariation::class, 'vendor_product_variation_id');
    }
}
