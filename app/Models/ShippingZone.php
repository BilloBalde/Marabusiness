<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = [
        'vendor_id',
        'name',
        'description',
        'country_code',
        'region',
        'cities',
        'radius_km',
        'base_price',
        'price_per_kg',
        'price_per_cbm',
        'price_per_item',
        'price_per_carton',
        'min_days',
        'max_days',
        'is_active',
    ];

    protected $casts = [
        'radius_km' => 'decimal:2',
        'base_price' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'price_per_cbm' => 'decimal:2',
        'price_per_item' => 'decimal:2',
        'price_per_carton' => 'decimal:2',
        'min_days' => 'integer',
        'max_days' => 'integer',
        'is_active' => 'boolean',
        'cities' => 'array', // Store as JSON array
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Relationships
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function carrierRates(): HasMany
    {
        return $this->hasMany(ShippingCarrierRate::class, 'zone_name', 'name')
            ->where('vendor_id', $this->vendor_id);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeForCountry($query, $countryCode)
    {
        return $query->where('country_code', $countryCode)
            ->orWhereNull('country_code');
    }

    public function scopeForRegion($query, $region)
    {
        return $query->where('region', $region)
            ->orWhereNull('region');
    }

    public function scopeForCity($query, $city)
    {
        return $query->whereJsonContains('cities', $city)
            ->orWhereNull('cities');
    }

    /**
     * Calculate delivery time range
     */
    public function getDeliveryTimeAttribute(): string
    {
        if ($this->min_days === $this->max_days) {
            return "{$this->min_days} jours";
        }
        return "{$this->min_days}-{$this->max_days} jours";
    }

    /**
     * Check if city is in this zone
     */
    public function hasCity(string $city): bool
    {
        if (empty($this->cities)) {
            return true; // Zone applies to all cities if none specified
        }
        
        return in_array($city, $this->cities);
    }

    /**
     * Check if address matches this zone
     */
    public function matchesAddress(array $address): bool
    {
        // Check country
        if ($this->country_code && $this->country_code !== ($address['country_code'] ?? $address['country'])) {
            return false;
        }

        // Check region/state
        if ($this->region && $this->region !== ($address['state'] ?? $address['region'])) {
            return false;
        }

        // Check city
        if (!empty($this->cities) && !$this->hasCity($address['city'] ?? '')) {
            return false;
        }

        return true;
    }

    /**
     * Calculate shipping cost based on weight, volume, and items
     */
    public function calculateCost(float $weight, float $cbm, int $items = 0, int $cartons = 0): float
    {
        $cost = $this->base_price;
        $cost += $this->price_per_kg * $weight;
        $cost += $this->price_per_cbm * $cbm;
        $cost += $this->price_per_item * $items;
        $cost += $this->price_per_carton * $cartons;

        // Ensure minimum charge
        $minimum = $this->country_code && $this->country_code !== $this->vendor->country ? 50 : 10;
        
        return max($minimum, $cost);
    }

    /**
     * Get zone display name
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = [];
        
        if ($this->name) {
            $parts[] = $this->name;
        }
        
        if ($this->country_code) {
            $parts[] = "({$this->country_code})";
        }
        
        if ($this->region) {
            $parts[] = "- {$this->region}";
        }
        
        if ($this->radius_km) {
            $parts[] = "({$this->radius_km} km)";
        }
        
        return implode(' ', $parts);
    }
}