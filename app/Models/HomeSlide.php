<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSlide extends Model
{
    protected $fillable = [
        'image',
        'title',
        'subtitle',
        'cta_label',
        'cta_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'title' => 'array',
        'subtitle' => 'array',
        'cta_label' => 'array',
        'is_active' => 'boolean',
    ];

    public function getTitleTextAttribute(): string
    {
        return $this->title[app()->getLocale()] ?? $this->title['fr'] ?? '';
    }

    public function getSubtitleTextAttribute(): string
    {
        return $this->subtitle[app()->getLocale()] ?? $this->subtitle['fr'] ?? '';
    }

    public function getCtaLabelTextAttribute(): string
    {
        return $this->cta_label[app()->getLocale()] ?? $this->cta_label['fr'] ?? '';
    }
}
