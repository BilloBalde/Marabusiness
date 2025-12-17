<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
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
    ];

    protected $appends = ['full_name', 'coordinates'];

    public function order()
    {
        return $this->belongsTo(Order::class);
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
