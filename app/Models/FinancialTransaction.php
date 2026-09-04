<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    const TYPE_ORDER = 'order';
    const TYPE_COMMISSION = 'commission';
    const TYPE_GATEWAY_FEE = 'gateway_fee';
    const TYPE_WIRE_FEE = 'wire_fee';
    const TYPE_PAYOUT = 'payout';
    const TYPE_REFUND = 'refund';
    
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_FAILED = 'failed';
    const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'order_id',
        'vendor_id',
        'transaction_type',
        'amount',
        'currency',
        'description',
        'reference_number',
        'gateway_fee',
        'commission_fee',
        'wire_fee',
        'net_amount',
        'status',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'gateway_fee' => 'float',
        'commission_fee' => 'float',
        'wire_fee' => 'float',
        'net_amount' => 'float',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function commissionSetting()
    {
        return $this->belongsTo(CommissionSetting::class);
    }
}