<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'name',
        'role',
        'message',
        'photo',
        'rating',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'message' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getMessageTextAttribute(): string
    {
        return $this->message[app()->getLocale()] ?? $this->message['fr'] ?? '';
    }
}
