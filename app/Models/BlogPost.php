<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPost extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'content',
        'cover_image',
        'category_id',
        'author_id',
        'tags',
        'published_at',
        'is_published',
        'views',
    ];

    protected $casts = [
        'title' => 'array',
        'excerpt' => 'array',
        'content' => 'array',
        'tags' => 'array',
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getTitleTextAttribute(): string
    {
        return $this->title[app()->getLocale()] ?? $this->title['fr'] ?? '';
    }

    public function getExcerptTextAttribute(): string
    {
        return $this->excerpt[app()->getLocale()] ?? $this->excerpt['fr'] ?? '';
    }

    public function getContentTextAttribute(): string
    {
        return $this->content[app()->getLocale()] ?? $this->content['fr'] ?? '';
    }

    public function registerView(): void
    {
        $this->increment('views');
    }
}
