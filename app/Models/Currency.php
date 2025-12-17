<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'precision',
        'is_active',
        'rate_to_usd'
    ];

    protected $casts = [
        'precision' => 'integer',
        'is_active' => 'boolean',
    ];

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
