<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Vendor extends Model
{
    use Concerns\HasTranslations;
    use HasFactory;

    public const SHIPPING_MODE_CARRIER  = 'carrier';
    public const SHIPPING_MODE_LOCALITY = 'locality';

    public const SHIPPING_MODES = [
        self::SHIPPING_MODE_CARRIER  => 'Zones et transporteurs',
        self::SHIPPING_MODE_LOCALITY => 'Prix par localité',
    ];
    protected $fillable = [
        'user_id',
        'currency_id',
        'store_name',
        'slug',
        'description',
        'logo_path',
        'is_active',
        'approved_at',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'latitude',
        'longitude',
        'shipping_zones',
        'carrier_rates',
        'shipping_mode',
        'default_shipping_amount',
        'international_shipping_surcharge',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'shipping_zones' => 'array',
        'carrier_rates' => 'array',
        'default_shipping_amount' => 'decimal:2',
        'international_shipping_surcharge' => 'decimal:2',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(VendorTranslation::class);
    }

    public function getStoreNameAttribute($value)
    {
        return $this->translate('store_name', $value);
    }

    public function getDescriptionAttribute($value)
    {
        return $this->translate('description', $value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getCurrencyCodeAttribute()
    {
        return strtoupper(trim($this->currency?->code ?? 'USD'));
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'vendor_product')
            ->withPivot([
                'id', // Add this to get vendor_product_id
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

    public function reviews(): HasMany
    {
        return $this->hasMany(VendorReview::class);
    }

    // Product reviews (through vendor products)
    public function productReviews()
    {
        return $this->hasManyThrough(
            VendorProductReview::class,
            VendorProduct::class,
            'vendor_id',
            'vendor_product_id',
            'id',
            'id'
        );
    }

    // NEW: Vendor-level reviews
    public function vendorReviews()
    {
        return $this->hasMany(VendorReview::class);
    }

    // NEW: Approved vendor reviews
    public function approvedVendorReviews()
    {
        return $this->vendorReviews()->where('is_approved', true);
    }

    // NEW: Followers relationship
    public function followers()
    {
        return $this->hasMany(VendorFollow::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Vendors default to the historic zone + carrier pricing. Only a vendor moved
     * to locality mode is quoted by LocalityShippingCalculator, against the shared
     * DeliveryZonePrice catalogue rather than a price of this vendor's own.
     */
    public function usesLocalityShipping(): bool
    {
        return $this->shipping_mode === self::SHIPPING_MODE_LOCALITY;
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute()
    {
        $parts = [];
        if ($this->address) $parts[] = $this->address;
        if ($this->city) $parts[] = $this->city;
        if ($this->state) $parts[] = $this->state;
        if ($this->zip_code) $parts[] = $this->zip_code;
        if ($this->country) $parts[] = $this->country;
        
        return implode(', ', $parts);
    }

    /**
     * Get shipping zones as array
     */
    public function getShippingZonesAttribute($value)
    {
        return json_decode($value ?? '[]', true) ?: [];
    }

    /**
     * Get carrier rates as array
     */
    public function getCarrierRatesAttribute($value)
    {
        return json_decode($value ?? '[]', true) ?: [];
    }

    /**
     * These three ran a fresh query per vendor per access — 20 extra queries for
     * 8 vendors on the homepage alone (10 rating averages, 10 follower counts).
     * A caller that eager-loads the aggregate — e.g.
     * Vendor::withAvg('approvedVendorReviews as vendor_rating', 'rating') — has
     * it sitting in $this->attributes under that same name already; each accessor
     * uses it when present and only falls back to a live query for a caller that
     * didn't eager-load, exactly like before.
     */
    public function getVendorRatingAttribute()
    {
        if (array_key_exists('vendor_rating', $this->attributes)) {
            return $this->attributes['vendor_rating'] ?? 0;
        }

        return $this->approvedVendorReviews()->avg('rating') ?? 0;
    }

    // NEW: Get vendor reviews count
    public function getVendorReviewsCountAttribute()
    {
        if (array_key_exists('vendor_reviews_count', $this->attributes)) {
            return (int) $this->attributes['vendor_reviews_count'];
        }

        return $this->approvedVendorReviews()->count();
    }

    // NEW: Get followers count
    public function getFollowersCountAttribute()
    {
        if (array_key_exists('followers_count', $this->attributes)) {
            return (int) $this->attributes['followers_count'];
        }

        return $this->followers()->count();
    }

    // NEW: Check if current user follows this vendor
    public function getIsFollowedByCurrentUserAttribute()
    {
        if (!auth()->check()) {
            return false;
        }
        
        return $this->followers()
            ->where('user_id', auth()->id())
            ->exists();
    }

    // NEW: Get all reviews (product + vendor) for display
    public function getAllReviews()
    {
        // Combine product reviews and vendor reviews
        $productReviews = $this->productReviews()
            ->with('user')
            ->get()
            ->map(function ($review) {
                return (object) [
                    'type' => 'product',
                    'id' => $review->id,
                    'user' => $review->user,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'product_name' => $review->vendorProduct->product->name ?? 'Product',
                ];
            });

        $vendorReviews = $this->approvedVendorReviews()
            ->with('user')
            ->get()
            ->map(function ($review) {
                return (object) [
                    'type' => 'vendor',
                    'id' => $review->id,
                    'user' => $review->user,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'product_name' => null,
                ];
            });

        // Both sides were just mapped into plain stdClass objects, but ->get()->map()
        // keeps returning an Eloquent Collection — and its merge() calls ->getKey() on
        // every item to de-duplicate by primary key, which stdClass doesn't have.
        // collect() drops down to the base Collection, whose merge() is a plain
        // array merge and never touches getKey().
        return collect($productReviews)->merge($vendorReviews)
            ->sortByDesc('created_at');
    }

    // NEW: Get overall rating (weighted average)
    public function getOverallRatingAttribute()
    {
        $productReviewsCount = $this->productReviews()->count();
        $vendorReviewsCount = $this->vendorReviewsCount;
        $totalReviews = $productReviewsCount + $vendorReviewsCount;

        if ($totalReviews === 0) {
            return 0;
        }

        $productRatingAvg = $this->productReviews()->avg('rating') ?? 0;
        $vendorRatingAvg = $this->vendorRating;

        // Weighted average (could adjust weights as needed)
        $weightedAvg = (
            ($productReviewsCount * $productRatingAvg) + 
            ($vendorReviewsCount * $vendorRatingAvg)
        ) / $totalReviews;

        return round($weightedAvg, 1);
    }
}
