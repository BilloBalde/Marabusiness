<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProductReview extends Model
{
    protected $fillable = [
        'vendor_product_id',
        'user_id',
        'rating',
        'comment',
    ];

    public function vendorProduct(): BelongsTo
    {
        return $this->belongsTo(VendorProduct::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

