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
        'has_variations',
        'variation_matrix',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'stock' => 'integer',
        'discount_percent' => 'integer',
        'sale_start' => 'datetime',
        'sale_end' => 'datetime',
        'is_active' => 'boolean',
        'variation_matrix' => 'array',
        'has_variations' => 'boolean',
    ];

    /**
     * Keeps a single offer per catalogue product: the cheapest among active vendors,
     * ties broken by the lowest id so paging stays stable.
     *
     * Listings query vendor_product directly, so a product carried by three shops
     * showed up three times. Written as a correlated NOT EXISTS rather than a GROUP BY
     * so it composes with pagination and survives MySQL's ONLY_FULL_GROUP_BY.
     *
     * Effective price is the sale price when there is a real one, else the list price.
     */
    public function scopeCheapestPerProduct($query)
    {
        $effective = fn (string $alias) => "COALESCE(NULLIF({$alias}.sale_price, 0), {$alias}.price)";

        return $query->whereNotExists(function ($sub) use ($effective) {
            $sub->selectRaw('1')
                ->from('vendor_product as vp_rival')
                ->join('vendors as v_rival', 'v_rival.id', '=', 'vp_rival.vendor_id')
                ->whereColumn('vp_rival.product_id', 'vendor_product.product_id')
                ->where('vp_rival.is_active', true)
                ->where('v_rival.is_active', true)
                ->whereRaw(
                    $effective('vp_rival') . ' < ' . $effective('vendor_product')
                    . ' OR (' . $effective('vp_rival') . ' = ' . $effective('vendor_product')
                    . ' AND vp_rival.id < vendor_product.id)'
                );
        });
    }

    // Relationships
    public function variations()
    {
        return $this->hasMany(VendorProductVariation::class, 'vendor_product_id');
    }

    // Helper methods
    public function getMinPriceAttribute()
    {
        if ($this->has_variations && $this->variations()->exists()) {
            return $this->variations()->min('price');
        }
        return $this->price;
    }

    public function getMaxPriceAttribute()
    {
        if ($this->has_variations && $this->variations()->exists()) {
            return $this->variations()->max('price');
        }
        return $this->price;
    }

    public function getTotalStockAttribute()
    {
        if ($this->has_variations && $this->variations()->exists()) {
            return $this->variations()->sum('stock');
        }
        return $this->stock;
    }
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

    public function getDisplayPriceAttribute(): float
    {
        // If product has variations => show min variation price
        if ($this->has_variations && $this->variations->count()) {
            // if you want to consider sale_price first:
            $minSale = $this->variations->whereNotNull('sale_price')->min('sale_price');
            if ($minSale !== null) {
                return (float) $minSale;
            }

            return (float) $this->variations->min('price');
        }

        // Simple product
        return (float) ($this->sale_price ?? $this->price ?? 0);
    }
}
