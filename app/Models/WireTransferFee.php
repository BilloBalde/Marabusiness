<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WireTransferFee extends Model
{
    protected $fillable = [
        'country',
        'currency',
        'fee_type', // percentage, fixed
        'percentage_fee',
        'fixed_fee',
        'minimum_amount',
        'maximum_amount',
        'processing_days',
        'is_active',
    ];

    protected $casts = [
        'percentage_fee' => 'float',
        'fixed_fee' => 'float',
        'minimum_amount' => 'float',
        'maximum_amount' => 'float',
        'is_active' => 'boolean',
        'processing_days' => 'integer',
    ];
}