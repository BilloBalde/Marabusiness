<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'first_name',
        'last_name',
        'phone',
        'street_address',
        'city',
        'state',
        'country',
        'zip_code',
        'latitude',     // 新增
        'longitude',    // 新增
        'zone',     
        'is_default',
        'locality_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected $appends = ['full_name', 'coordinates'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Delivery locality, used instead of the postal code to price shipping for
     * vendors on the locality pricing mode.
     */
    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getCoordinatesAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "{$this->latitude}, {$this->longitude}";
        }
        return null;
    }

    public function getFormattedAddressAttribute(): string
    {
        $parts = [
            $this->street_address,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country,
        ];
        
        return implode(', ', array_filter($parts));
    }
    
    public function calculateAndSetZone(array $vendorLocation): bool
    {
        $calculator = new ZoneCalculatorService();
        $zone = $calculator->calculateZoneForAddress($this, $vendorLocation);
        
        if ($zone) {
            $this->zone = $zone;
            return $this->save();
        }
        
        return false;
    }
}
