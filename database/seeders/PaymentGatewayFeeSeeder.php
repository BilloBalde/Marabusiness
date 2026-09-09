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
                // Mobile checkout used to record LengoPay orders as 'stripe' and was therefore
                // billed at the Stripe rate. These values reproduce that exact behaviour so the
                // rename changes no invoice — replace them with LengoPay's real tariff.
                'gateway_name' => 'lengopay',
                'fee_type' => 'percentage_plus_fixed',
                'percentage_fee' => 2.9,
                'fixed_fee' => 0.30,
                'currency' => 'USD',
                'minimum_fee' => 0.50,
                'description' => 'LengoPay processing fee (rate to confirm)',
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

        // Keyed on gateway_name so the seeder can be re-run to pick up a new gateway
        // or an amended tariff without duplicating rows or failing on a unique index.
        foreach ($fees as $fee) {
            PaymentGatewayFee::updateOrCreate(
                ['gateway_name' => $fee['gateway_name']],
                $fee
            );
        }
    }
}