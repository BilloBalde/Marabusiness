<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject; // or AsCollection

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_amount',
        'total_amount',
        'variation_json', // Added
    ];

    // Cast the JSON column to array or collection
    protected $casts = [
        'variation_json' => 'array', // or AsArrayObject::class, or AsCollection::class
        'unit_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function hasVariations(): bool
    {
        return !empty($this->variation_json) || !empty($this->variation_note);
    }

    // Helper method to get formatted variations
    public function getVariationTextAttribute(): string
    {
        if (empty($this->variation_json)) {
            return '';
        }

        $variations = $this->variation_json;
        $parts = [];

        foreach ($variations as $key => $value) {
            // Format: "Size: Large", "Color: Red"
            $parts[] = ucfirst($key) . ': ' . $value;
        }

        return implode(', ', $parts);
    }

    // Helper method to get specific variation
    public function getVariation(string $key, $default = null)
    {
        return $this->variation_json[$key] ?? $default;
    }

    // Helper method to set variations
    public function setVariations(array $variations): void
    {
        $this->variation_json = $variations;
    }
}