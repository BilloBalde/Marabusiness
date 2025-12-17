<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'carrier',
        'tracking_number',
        'status',
        'current_location',
        'estimated_delivery_at',
        'last_synced_at',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'estimated_delivery_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public const CARRIERS = [
        'dhl' => 'DHL',
        'ups' => 'UPS',
        'fedex' => 'FedEx',
        'chrono' => 'Chronopost',
        'local' => 'Local Courier',
        'other' => 'Other',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
