<?php

namespace Tests\Feature\Integrity;

use App\Models\Currency;
use App\Models\PaymentGatewayFee;
use App\Services\FinanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * FinanceCalculator::calculateGatewayFee() finds the fee with
 * where('gateway_name', $paymentMethod), so the configured name has to be the
 * value an order actually carries.
 *
 * It did not. Orders store 'om' — the value in Paiement::OFFLINE_METHODS, in the
 * API validation rules and in the mobile app — while the fee row said
 * 'orange_money'. The lookup found nothing and returned 0 for all 38 Orange
 * Money orders. 'cod', the most common method of all at 78 orders, has no row at
 * all; the table holds 'cash', which is a different value.
 *
 * These tests pin the join, not the amounts: what the platform charges is a
 * business decision, but a fee that is configured has to be the one that gets
 * applied.
 */
class GatewayFeeLookupTest extends TestCase
{
    use RefreshDatabase;

    private function fee(string $gatewayName, float $percentage, ?float $minimum = null): PaymentGatewayFee
    {
        return PaymentGatewayFee::create([
            'gateway_name' => $gatewayName,
            'fee_type' => 'percentage',
            'percentage_fee' => $percentage,
            'fixed_fee' => 0,
            'minimum_fee' => $minimum,
            'currency' => 'GNF',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function an_orange_money_order_is_charged_the_om_fee(): void
    {
        $this->fee('om', 1.5);

        $fee = (new FinanceCalculator())->calculateGatewayFee(1000000, 'om');

        $this->assertSame(15000.0, $fee, '1.5% of 1,000,000');
    }

    #[Test]
    public function a_fee_filed_under_the_old_name_is_never_found(): void
    {
        // The exact shape of the bug: a row exists, is active, and is simply
        // never matched, so the platform quietly collects nothing.
        $this->fee('orange_money', 1.5);

        $fee = (new FinanceCalculator())->calculateGatewayFee(1000000, 'om');

        $this->assertSame(0.0, $fee, 'a name the orders do not use cannot be charged');
    }

    #[Test]
    public function the_minimum_fee_applies_when_the_percentage_falls_below_it(): void
    {
        $this->fee('om', 1.5, 1500);

        // 1.5% of 50,000 is 750, under the 1,500 floor.
        $this->assertSame(1500.0, (new FinanceCalculator())->calculateGatewayFee(50000, 'om'));
    }

    #[Test]
    public function a_method_with_no_configured_fee_is_charged_nothing(): void
    {
        // Cash on delivery is the most common method in the catalogue and has no
        // row. That is a business decision, not a bug — but it should be a
        // deliberate zero, which is what this pins.
        Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);

        $this->assertSame(0.0, (new FinanceCalculator())->calculateGatewayFee(1000000, 'cod'));
    }

    #[Test]
    public function an_inactive_fee_is_not_charged(): void
    {
        PaymentGatewayFee::create([
            'gateway_name' => 'om',
            'fee_type' => 'percentage',
            'percentage_fee' => 1.5,
            'fixed_fee' => 0,
            'currency' => 'GNF',
            'is_active' => false,
        ]);

        $this->assertSame(0.0, (new FinanceCalculator())->calculateGatewayFee(1000000, 'om'));
    }
}
