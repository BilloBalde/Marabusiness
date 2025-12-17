<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'options'
    ];

    protected $casts = [
        'options' => 'array',
    ];

    // Helper method to get setting value
    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    // Helper method to get all settings by group
    public static function getByGroup($group)
    {
        return static::where('group', $group)->get()->pluck('value', 'key');
    }
}