<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'vendor_product_id',
        'product_id',
        'quantity',
        'purchase_price',
        'total_cost_items',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'purchase_price'         => 'decimal:2',
        'total_cost_items'  => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function vendorProduct(): BelongsTo
    {
        return $this->belongsTo(VendorProduct::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
