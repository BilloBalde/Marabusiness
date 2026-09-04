<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use Concerns\HasTranslations;

    protected $fillable = [
        'name',
        'icon',
        'slug',
        'description',
        'features'
    ];

    protected $casts = [
        'features' => 'array', // Auto-cast JSON to array
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ServiceTranslation::class);
    }

    public function getNameAttribute($value)
    {
        return $this->translate('name', $value);
    }

    public function getDescriptionAttribute($value)
    {
        return $this->translate('description', $value);
    }

    /**
     * Get features as array with default if empty
     */
    public function getFeaturesAttribute($value)
    {
        $features = json_decode($value, true) ?? [];
        
        // If empty, return default features
        if (empty($features)) {
            return [
                'Professional and reliable service',
                '24/7 customer support',
                'Quality guaranteed',
                'Flexible service options'
            ];
        }
        
        return $features;
    }
    
    /**
     * Set features attribute
     */
    public function setFeaturesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['features'] = json_encode($value);
        } else {
            $this->attributes['features'] = $value;
        }
    }
}
