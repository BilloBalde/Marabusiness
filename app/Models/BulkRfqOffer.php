<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkRfqOffer extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted_by_buyer';
    public const STATUS_REJECTED = 'rejected_by_buyer';

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
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Total of the quote, expressed in the offer's own currency.
     */
    public function total(): float
    {
        return ((float) $this->unit_price * (int) $this->moq) + (float) $this->shipping_cost;
    }
}
