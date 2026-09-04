<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Concerns\HasTranslations;
    protected $fillable = ['name', 'image', 'slug', 'family', 'is_active', 'created_by'];
    protected $with = ['translations'];

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    // Auto-translate name when you call $category->name
    public function getNameAttribute($value)
    {
        return $this->translate('name') ?? $value;
    }

    public function getFamilyAttribute($value)
    {
        return $this->translate('family') ?? $value;
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
