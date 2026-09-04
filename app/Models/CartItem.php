<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'vendor_product_id',
        'variation_id',
        'quantity',
        'selected_variations',
        'custom_note',
        'cart_key',
    ];

    protected $casts = [
        'selected_variations' => 'array',
        'quantity' => 'integer',
    ];
}
