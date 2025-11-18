<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Paiement extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'payment_method',
        'payment_status',
        'currency',
        'amount',
        'image',
        'transaction_id' // Added transaction_id to fillable attributes
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
