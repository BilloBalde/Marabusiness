<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorFollow extends Model
{
    protected $fillable = ['user_id', 'vendor_id'];
    
    public $timestamps = true;
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}