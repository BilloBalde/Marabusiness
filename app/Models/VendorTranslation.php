<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorTranslation extends Model
{
    protected $fillable = ['vendor_id', 'locale', 'store_name', 'description'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
