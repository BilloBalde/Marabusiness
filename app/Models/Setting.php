<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * The brief for a section (e.g. 'sourcing_intro'), in the current locale —
     * falling back to French, then to '' rather than null so callers never
     * need an extra null-check just to print it.
     */
    public static function text(string $key): string
    {
        $value = static::where('key', $key)->value('value');

        if (! is_array($value)) {
            return '';
        }

        return $value[app()->getLocale()] ?? $value['fr'] ?? '';
    }
}
