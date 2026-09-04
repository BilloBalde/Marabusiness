<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    protected $fillable = [
        'vendor_id', // null for global settings
        'commission_type', // percentage, fixed
        'commission_rate', // e.g., 10 for 10%
        'minimum_amount',
        'maximum_amount',
        'payment_method', // null for all, or 'stripe', 'orange_money'
        'is_active',
        'notes',
    ];

    protected $casts = [
        'commission_rate' => 'float',
        'minimum_amount' => 'float',
        'maximum_amount' => 'float',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}