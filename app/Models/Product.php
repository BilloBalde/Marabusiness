<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'quantity',
        'images',
        'brand_id',
        'category_id',
        'is_active',
        'is_featured',
        'in_stock',
        'on_sale',
        'weight',
        'length',
        'width',
        'height',
        'cbm',
        'weight_unit',
        'dimension_unit',
    ];

    protected $casts = [
        'images' => 'array',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'cbm' => 'decimal:4',
    ];
    

    public function calculateCBM()
    {
        if (!$this->length || !$this->width || !$this->height) {
            return 0;
        }

        // Convert to meters for CBM calculation
        $length = $this->convertToMeters($this->length, $this->dimension_unit);
        $width = $this->convertToMeters($this->width, $this->dimension_unit);
        $height = $this->convertToMeters($this->height, $this->dimension_unit);

        return $length * $width * $height;
    }

    /**
     * Calculate volumetric weight
     */
    public function calculateVolumetricWeight()
    {
        $cbm = $this->calculateCBM();
        // Volumetric weight formula: CBM * 167 (standard conversion factor)
        return $cbm * 167;
    }

    /**
     * Convert dimension to meters
     */
    private function convertToMeters($value, $unit)
    {
        return match($unit) {
            'cm' => $value / 100,
            'm' => $value,
            'in' => $value * 0.0254,
            'ft' => $value * 0.3048,
            default => $value / 100, // default to cm conversion
        };
    }

    /**
     * Convert weight to kg
     */
    public function getWeightInKg()
    {
        return match($this->weight_unit) {
            'kg' => $this->weight,
            'g' => $this->weight / 1000,
            'lb' => $this->weight * 0.453592,
            'oz' => $this->weight * 0.0283495,
            default => $this->weight,
        };
    }

    /**
     * Calculate shipping weight (actual or volumetric, whichever is greater)
     */
    public function getShippingWeight()
    {
        $actualWeight = $this->getWeightInKg();
        $volumetricWeight = $this->calculateVolumetricWeight();
        
        return max($actualWeight, $volumetricWeight);
    }

    /**
     * Get dimensions formatted as string
     */
    public function getDimensionsFormatted()
    {
        if (!$this->length || !$this->width || !$this->height) {
            return 'N/A';
        }
        
        return "{$this->length} × {$this->width} × {$this->height} {$this->dimension_unit}";
    }

    // Add these methods to your Product model:

    /**
     * Get dimensions formatted as string
     */
    public function getDimensionsFormattedAttribute()
    {
        if (!$this->length || !$this->width || !$this->height) {
            return 'N/A';
        }
        
        return "{$this->length} × {$this->width} × {$this->height} {$this->dimension_unit}";
    }

    /**
     * Get weight with unit
     */
    public function getWeightFormattedAttribute()
    {
        if (!$this->weight) {
            return 'N/A';
        }
        
        return number_format($this->weight, 2) . ' ' . $this->weight_unit;
    }

    /**
     * Get CBM formatted
     */
    public function getCbmFormattedAttribute()
    {
        if (!$this->cbm) {
            return 'N/A';
        }
        
        return number_format($this->cbm, 4) . ' m³';
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendor_product')
            ->withPivot([
                'price',
                'sale_price',
                'discount_percent',
                'stock',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function vendorProducts()
    {
        return $this->hasMany(VendorProduct::class);
    }

    public function globalRating(): float
    {
        $ratings = $this->vendorProducts->flatMap(function ($vp) {
            return $vp->reviews->pluck('rating');
        });

        return round(($ratings->avg() ?? 0), 1);
    }

    public function totalReviews(): int
    {
        return $this->vendorProducts->sum(fn($vp) => $vp->reviews->count());
    }

    public function getImagesAttribute($value)
    {
        if (!$value) return [];
        try {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getVariationJsonAttribute($value)
    {
        if (!$value) return [];
        try {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Exception $e) {
            return [];
        }
    }


}
