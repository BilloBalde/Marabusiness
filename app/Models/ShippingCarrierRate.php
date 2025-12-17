<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingCarrierRate extends Model
{
    protected $fillable = [
        'vendor_id',
        'carrier',
        'zone_name',
        'base_rate',
        'rate_per_kg',
        'rate_per_cbm',
        'rate_per_item',
        'rate_per_carton',
        'min_rate',
        'max_rate',
        'delivery_days',
        'free_shipping_threshold',
        'is_active',
        'is_default',
        'carrier_account_id',
        'service_code',
    ];

    protected $casts = [
        'base_rate' => 'decimal:2',
        'rate_per_kg' => 'decimal:2',
        'rate_per_cbm' => 'decimal:2',
        'rate_per_item' => 'decimal:2',
        'rate_per_carton' => 'decimal:2',
        'min_rate' => 'decimal:2',
        'max_rate' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'delivery_days' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_default' => false,
        'min_rate' => 0,
    ];

    /**
     * Carrier options
     */
    public const CARRIERS = [
        'local' => 'Local Courier',
        'chrono' => 'Chronopost',
        'dhl' => 'DHL Express',
        'ups' => 'UPS',
        'fedex' => 'FedEx',
        'cma' => 'CMA CGM',
        'msk' => 'Maersk',
        'msc' => 'MSC',
        'express' => 'Express Delivery',
        'standard' => 'Standard Delivery',
        'economy' => 'Economy Delivery',
    ];

    /**
     * Relationships
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zone_name', 'name')
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

    public function scopeForZone($query, $zoneName)
    {
        return $query->where('zone_name', $zoneName);
    }

    public function scopeForCarrier($query, $carrier)
    {
        return $query->where('carrier', $carrier);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get carrier display name
     */
    public function getCarrierNameAttribute(): string
    {
        return self::CARRIERS[$this->carrier] ?? ucfirst($this->carrier);
    }

    /**
     * Get estimated delivery date
     */
    public function getEstimatedDeliveryAttribute(): string
    {
        if ($this->delivery_days === 0) {
            return 'Same day';
        } elseif ($this->delivery_days === 1) {
            return 'Next day';
        } else {
            return "{$this->delivery_days} business days";
        }
    }

    /**
     * Calculate shipping cost
     */
    public function calculateCost(float $weight, float $cbm, int $items = 0, int $cartons = 0): float
    {
        $cost = $this->base_rate;
        $cost += $this->rate_per_kg * $weight;
        $cost += $this->rate_per_cbm * $cbm;
        $cost += $this->rate_per_item * $items;
        $cost += $this->rate_per_carton * $cartons;

        // Apply min/max rates
        if ($this->min_rate && $cost < $this->min_rate) {
            $cost = $this->min_rate;
        }
        
        if ($this->max_rate && $cost > $this->max_rate) {
            $cost = $this->max_rate;
        }

        return round($cost, 2);
    }

    /**
     * Check if free shipping applies
     */
    public function isFreeShipping(float $orderTotal): bool
    {
        return $this->free_shipping_threshold > 0 
            && $orderTotal >= $this->free_shipping_threshold;
    }

    /**
     * Get delivery time description
     */
    public function getDeliveryDescriptionAttribute(): string
    {
        $descriptions = [
            'local' => 'Livraison locale - 1-2 jours',
            'chrono' => 'Chronopost Express - 24-48h',
            'dhl' => 'DHL Express - 2-3 jours',
            'ups' => 'UPS Standard - 3-5 jours',
            'fedex' => 'FedEx Priority - 2-4 jours',
            'express' => 'Express - 1-3 jours',
            'standard' => 'Standard - 3-7 jours',
            'economy' => 'Économique - 7-14 jours',
        ];

        return $descriptions[$this->carrier] ?? "Livraison en {$this->delivery_days} jours";
    }

    /**
     * Validate if this rate can be used for given parameters
     */
    public function isValidFor(float $weight, float $cbm, string $destinationCountry = null): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check if zone exists and is active
        $zone = $this->zone;
        if (!$zone || !$zone->is_active) {
            return false;
        }

        // Check country if specified
        if ($destinationCountry && $zone->country_code && $zone->country_code !== $destinationCountry) {
            return false;
        }

        return true;
    }
}