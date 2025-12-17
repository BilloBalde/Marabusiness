<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorProductWholesale extends Model
{
    protected $table = 'vendor_product_wholesale';
    protected $fillable = [
        'vendor_product_id',
        'min_qty',
        'max_qty',
        'price',
    ];

    public function vendorProduct()
    {
        return $this->belongsTo(VendorProduct::class);
    }
}
