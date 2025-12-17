<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkRfqOffer extends Model
{
    protected $fillable = [
        'bulk_rfq_id',
        'vendor_id',
        'moq',
        'unit_price',
        'currency',
        'lead_time_days',
        'shipping_terms',
        'shipping_cost',
        'vendor_notes',
        'status',
    ];

    protected $casts = [
        'moq' => 'integer',
        'unit_price' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'lead_time_days' => 'integer',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(BulkRfq::class, 'bulk_rfq_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted_by_buyer';
    }
}
