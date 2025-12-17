<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BulkRfqMessage extends Model
{
    protected $fillable = [
        'bulk_rfq_id',
        'sender_type',
        'sender_id',
        'message',
    ];

    /** --------------------------
     *  RELATIONSHIPS
     * -------------------------- */

    public function rfq()
    {
        return $this->belongsTo(BulkRfq::class, 'bulk_rfq_id');
    }

    // polymorphic sender (User or VendorUser)
    public function sender():MorphTo
    {
        return $this->morphTo();
    }

    public function isFromUser(): bool
    {
        return $this->sender_type === \App\Models\User::class; // Use full namespace
    }

    // Helper to check if sender is vendor - FIXED
    public function isFromVendor(): bool
    {
        return $this->sender_type === \App\Models\Vendor::class; // Use full namespace
    }
}
