<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    public function definition(): array
    {
        $storeName = fake()->unique()->company();

        return [
            'user_id' => User::factory(),
            'currency_id' => Currency::factory(),
            'store_name' => $storeName,
            'slug' => Str::slug($storeName) . '-' . Str::lower(Str::random(6)),
            'is_active' => true,
            'approved_at' => now(),
            'country' => 'Guinea',
            'shipping_mode' => Vendor::SHIPPING_MODE_CARRIER,
        ];
    }
}
