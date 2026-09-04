<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPayout extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'vendor_id',
        'amount',
        'currency',
        'commission_amount',
        'gateway_fees',
        'wire_fees',
        'net_amount',
        'payout_method', // bank_wire, orange_money, stripe_transfer
        'payout_details',
        'status',
        'reference_number',
        'processed_by',
        'processed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'float',
        'commission_amount' => 'float',
        'gateway_fees' => 'float',
        'wire_fees' => 'float',
        'net_amount' => 'float',
        'payout_details' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class)->with('currency');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class, 'reference_number', 'reference_number');
    }
}