<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTranslation extends Model
{
    protected $fillable = ['product_id', 'locale', 'name', 'short_description', 'description'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
