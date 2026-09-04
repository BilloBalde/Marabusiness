<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasTranslations;

class Brand extends Model
{
    use HasTranslations;
    protected $fillable = ['name', 'image', 'slug', 'is_active', 'created_by'];
    protected $with = ['translations'];

    public function translations(): HasMany
    {
        return $this->hasMany(BrandTranslation::class);
    }

    // Auto-translate name when you call $brand->name
    public function getNameAttribute($value)
    {
        // $value is the raw DB 'name', NOT a locale
        return $this->translate('name') ?? $value;
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
