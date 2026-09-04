<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorProductReviewTranslation extends Model
{
    protected $fillable = ['vendor_product_review_id', 'locale', 'comment'];
 
    public function vendorProductReview()
    {
        return $this->belongsTo(VendorProductReview::class);
    }
}
