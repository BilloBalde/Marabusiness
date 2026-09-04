<?php

namespace Database\Seeders;

use App\Models\PaymentGatewayFee;
use Illuminate\Database\Seeder;

class PaymentGatewayFeeSeeder extends Seeder
{
    public function run(): void
    {
        $fees = [
            [
                'gateway_name' => 'stripe',
                'fee_type' => 'percentage_plus_fixed',
                'percentage_fee' => 2.9,
                'fixed_fee' => 0.30,
                'currency' => 'USD',
                'minimum_fee' => 0.50,
                'description' => 'Standard Stripe processing fee',
                'is_active' => true,
            ],
            [
                'gateway_name' => 'orange_money',
                'fee_type' => 'percentage',
                'percentage_fee' => 1.5,
                'fixed_fee' => 0,
                'currency' => 'GNF',
                'description' => 'Orange Money transaction fee',
                'is_active' => true,
            ],
            [
                'gateway_name' => 'cash',
                'fee_type' => 'fixed',
                'percentage_fee' => 0,
                'fixed_fee' => 0,
                'currency' => 'USD',
                'description' => 'Cash payment - no fees',
                'is_active' => true,
            ],
        ];

        foreach ($fees as $fee) {
            PaymentGatewayFee::create($fee);
        }
    }
}