<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'US Dollar',
            // Unique per call: tests that create several vendors each get their own
            // Currency::factory() row, and 'code' is unique at the DB level.
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'symbol' => '$',
            'precision' => 2,
            'is_active' => true,
            'rate_to_usd' => 1,
        ];
    }
}
