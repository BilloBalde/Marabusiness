<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorProduct extends Model
{
    protected $table = 'vendor_product';

    protected $fillable = [
        'vendor_id',
        'product_id',
        'price',
        'sale_price',
        'purchase_price',
        'discount_percent',
        'stock',
        'is_active',
        'sale_start',
        'sale_end',
        'variation_json'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'stock' => 'integer',
        'discount_percent' => 'integer',
        'sale_start' => 'datetime',
        'sale_end' => 'datetime',
        'is_active' => 'boolean',
        'variation_json' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(VendorProductReview::class);
    }

    public function averageRating(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function ratingCount(): int
    {
        return $this->reviews()->count();
    }

    public function wholesaleTiers()
    {
        return $this->hasMany(VendorProductWholesale::class);
    }

}
