<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorReview extends Model
{
    protected $fillable = [
        'user_id', 
        'vendor_id', 
        'rating', 
        'comment', 
        'is_approved'
    ];
    
    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
    
    // Scope for approved reviews
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
    
    // Scope for pending reviews
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }
}