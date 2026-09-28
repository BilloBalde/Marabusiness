<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sourcing extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'short_description',
        'description',
        'gallery',
        'external_links',
        'catalog_pdf',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'title' => 'array',
        'short_description' => 'array',
        'description' => 'array',
        'gallery' => 'array',
        'external_links' => 'array',
        'is_active' => 'boolean',
    ];

    public function sourceProducts(): HasMany
    {
        return $this->hasMany(SourceProduct::class);
    }

    /**
     * Only 'fr' is ever populated today — the JSON shape ({fr, en, zh}) is
     * there so the other two languages can be added later without a migration,
     * not because they're filled in now.
     */
    public function getTitleTextAttribute(): string
    {
        return $this->title[app()->getLocale()] ?? $this->title['fr'] ?? '';
    }

    public function getShortDescriptionTextAttribute(): string
    {
        return $this->short_description[app()->getLocale()] ?? $this->short_description['fr'] ?? '';
    }

    public function getDescriptionTextAttribute(): string
    {
        return $this->description[app()->getLocale()] ?? $this->description['fr'] ?? '';
    }
}
