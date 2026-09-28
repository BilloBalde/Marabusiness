<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'sourcing_id',
        'slug',
        'name',
        'short_description',
        'price_range',
        'quantity_minimum',
        'city',
        'interest_count',
        'images',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'short_description' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    public function sourcing(): BelongsTo
    {
        return $this->belongsTo(Sourcing::class);
    }

    public function getNameTextAttribute(): string
    {
        return $this->name[app()->getLocale()] ?? $this->name['fr'] ?? '';
    }

    public function getShortDescriptionTextAttribute(): string
    {
        return $this->short_description[app()->getLocale()] ?? $this->short_description['fr'] ?? '';
    }

    /** Called once per genuine selection — the click that opens the quote flow, not a page view. */
    public function registerInterest(): void
    {
        $this->increment('interest_count');
    }
}
