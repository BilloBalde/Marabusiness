<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayFee extends Model
{
    protected $fillable = [
        'gateway_name', // stripe, orange_money, etc.
        'fee_type', // percentage, fixed, percentage+fixed
        'percentage_fee',
        'fixed_fee',
        'currency',
        'minimum_fee',
        'maximum_fee',
        'is_active',
        'description',
    ];

    protected $casts = [
        'percentage_fee' => 'float',
        'fixed_fee' => 'float',
        'minimum_fee' => 'float',
        'maximum_fee' => 'float',
        'is_active' => 'boolean',
    ];
}