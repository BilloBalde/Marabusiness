<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Locality extends Model
{
    public const TYPE_REGION     = 'region';
    public const TYPE_PREFECTURE = 'prefecture';
    public const TYPE_COMMUNE    = 'commune';
    public const TYPE_VILLE      = 'ville';
    public const TYPE_QUARTIER   = 'quartier';

    public const TYPES = [
        self::TYPE_REGION     => 'Région',
        self::TYPE_PREFECTURE => 'Préfecture',
        self::TYPE_COMMUNE    => 'Commune',
        self::TYPE_VILLE      => 'Ville',
        self::TYPE_QUARTIER   => 'Quartier',
    ];

    protected $fillable = [
        'parent_id',
        'name',
        'type',
        'country_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function shippingRates(): HasMany
    {
        return $this->hasMany(VendorShippingRate::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Localities a buyer can actually be delivered to. Only regions are excluded:
     * they exist to group the pickers. Outside Conakry the prefecture is the unit
     * people deliver to, while Conakry is split into communes.
     */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->active()->where('type', '!=', self::TYPE_REGION);
    }

    /**
     * "Ratoma (Conakry)" — disambiguates places that share a name across prefectures.
     */
    public function getFullNameAttribute(): string
    {
        return $this->parent
            ? "{$this->name} ({$this->parent->name})"
            : $this->name;
    }
}
