<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProductVariation extends Model
{
    protected $table = 'vendor_product_variations';
    protected $fillable = [
        'vendor_product_id',
        'attributes',
        'price',
        'sale_price',
        'stock',
        'sku',
        'image',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function vendorProduct(): BelongsTo
    {
        return $this->belongsTo(VendorProduct::class, 'vendor_product_id');
    }

    public function getAttributesDisplayAttribute()
    {
        return collect($this->attributes)
            ->map(fn($value, $key) => "{$key}: {$value}")
            ->join(', ');
    }
    
    public function getPriceDisplayAttribute()
    {
        $price = $this->sale_price ?? $this->price;
        return number_format($price, 2) . ' ' . ($this->vendorProduct->vendor->currency->code ?? 'USD');
    }

    public function wholesaleTiers()
    {
        return $this->hasMany(VendorProductVariationWholesaleTier::class, 'vendor_product_variation_id')
            ->orderBy('min_qty');
    }

    public function getWholesalePriceForQty(int $qty): ?float
    {
        return $this->wholesaleTiers()
            ->where('min_qty', '<=', $qty)
            ->where(function ($q) use ($qty) {
                $q->whereNull('max_qty')->orWhere('max_qty', '>=', $qty);
            })
            ->orderByDesc('min_qty')
            ->value('price');
    }

    public function getPrettyAttributesAttribute(): string
    {
        $attrs = $this->getAttribute('attributes') ?? []; // ✅ JSON column only

        return collect($attrs)
            ->map(fn ($value) => strtoupper((string) $value))
            ->implode(' | ');
    }


}