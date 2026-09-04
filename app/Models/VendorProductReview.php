<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorProductReview extends Model
{
    use Concerns\HasTranslations;
    protected $fillable = [
        'vendor_product_id',
        'user_id',
        'rating',
        'comment',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(VendorProductReviewTranslation::class);
    }

    public function getCommentAttribute($value)
    {
        return $this->translate('comment', $value);
    }

    public function vendorProduct(): BelongsTo
    {
        return $this->belongsTo(VendorProduct::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
