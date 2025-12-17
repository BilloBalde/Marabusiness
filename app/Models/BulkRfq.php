<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkRfq extends Model
{
    protected $fillable = [
        'user_id',
        'vendor_id',
        'product_id',
        'vendor_product_id',
        'status',
        'quantity',
        'target_price',
        'currency',
        'shipping_country',
        'shipping_city',
        'shipping_port',
        'needs_customization',
        'customization_notes',
    ];

    protected $casts = [
        'needs_customization' => 'boolean',
        'target_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /** --------------------------
     *  RELATIONSHIPS
     * -------------------------- */

    // Buyer
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Vendor receiving RFQ
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // Product reference
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Vendor product (for prices)
    public function vendorProduct()
    {
        return $this->belongsTo(VendorProduct::class);
    }

    // Offers from vendors
    public function offers()
    {
        return $this->hasMany(BulkRfqOffer::class);
    }

    // Chat messages
    public function messages()
    {
        return $this->hasMany(BulkRfqMessage::class);
    }

    public function latestOffer()
    {
        return $this->hasOne(BulkRfqOffer::class)->latest();
    }

    // Status helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isQuoted(): bool
    {
        return $this->status === 'quoted';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    // Scope for user's RFQs
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Scope for vendor's RFQs
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }
}
