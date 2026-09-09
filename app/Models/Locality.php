<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Locality extends Model
{
    public const TYPE_COUNTRY    = 'country';
    public const TYPE_REGION     = 'region';
    public const TYPE_PREFECTURE = 'prefecture';
    public const TYPE_COMMUNE    = 'commune';
    public const TYPE_VILLE      = 'ville';
    public const TYPE_QUARTIER   = 'quartier';

    public const TYPES = [
        self::TYPE_COUNTRY    => 'Pays',
        self::TYPE_REGION     => 'Région',
        self::TYPE_PREFECTURE => 'Préfecture',
        self::TYPE_COMMUNE    => 'Commune',
        self::TYPE_VILLE      => 'Ville',
        self::TYPE_QUARTIER   => 'Quartier',
    ];

    /**
     * Levels that group the pickers rather than name an actual delivery destination.
     * A shared price attaches to a leaf (commune/ville/quartier/prefecture), never to
     * a bare country or region node.
     */
    public const NON_SELECTABLE_TYPES = [
        self::TYPE_COUNTRY,
        self::TYPE_REGION,
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

    /**
     * The one shared delivery price for this zone, if anyone has set it — visible to
     * every vendor, not owned by whoever created it. See App\Models\DeliveryZonePrice.
     */
    public function deliveryZonePrice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DeliveryZonePrice::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Localities a buyer can actually be delivered to. Country and region only group
     * the pickers. Outside Conakry the prefecture is the unit people deliver to, while
     * Conakry is split into communes; a city added under another country is selectable
     * the same way.
     */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->active()->whereNotIn('type', self::NON_SELECTABLE_TYPES);
    }

    /**
     * "Ratoma (Conakry)" — disambiguates places that share a name across regions.
     * Used on the buyer-facing checkout picker, where the storefront's own country is
     * already implied.
     */
    public function getFullNameAttribute(): string
    {
        return $this->parent
            ? "{$this->name} ({$this->parent->name})"
            : $this->name;
    }

    /**
     * "Ratoma, Conakry, Guinée" — the full chain up to the country. Used on the
     * admin/vendor pricing screen, which spans every country and needs the full path
     * to avoid ambiguity between, say, two unrelated regions that happen to share a name.
     */
    public function getBreadcrumbAttribute(): string
    {
        $names = [$this->name];
        $node = $this->parent;

        while ($node) {
            $names[] = $node->name;
            $node = $node->parent;
        }

        return implode(', ', $names);
    }
}
