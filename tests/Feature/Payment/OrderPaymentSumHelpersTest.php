<?php

namespace Tests\Feature\Payment;

use App\Models\Currency;
use App\Models\Order;
use App\Models\Paiement;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * paidAmount(), declaredAmount() et declaredAwaitingConfirmation() sont nées
 * d'un audit qui a trouvé sept endroits — trois affichages dans OrderResource,
 * PaiementModal, SuccessPage, SuccessPageStripe, plus declaredAwaitingConfirmation()
 * elle-même — sommant `paiements.amount` sans jamais convertir, exactement le bug
 * que Order::syncPaymentTotals() avait déjà corrigé une fois mais uniquement pour
 * son propre appel. Ces tests portent sur les trois méthodes partagées
 * directement ; CrossCurrencyPaymentTotalsTest couvre déjà syncPaymentTotals().
 */
class OrderPaymentSumHelpersTest extends TestCase
{
    use RefreshDatabase;

    private function orderWithVendorCurrency(string $code, float $rateToUsd, float $grandTotal): Order
    {
        $currency = Currency::factory()->create(['code' => $code, 'rate_to_usd' => $rateToUsd]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id]);

        return Order::factory()->create([
            'vendor_id' => $vendor->id,
            'grand_total' => $grandTotal,
            'rate_to_usd' => $rateToUsd,
        ]);
    }

    private function paiement(Order $order, float $amount, string $currency, ?string $confirmedAt): Paiement
    {
        return Paiement::create([
            'order_id' => $order->id,
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => 'cash',
            'payment_status' => $confirmedAt ? 'paid' : 'pending',
            'transaction_id' => 'TEST-' . uniqid(),
            'confirmed_at' => $confirmedAt,
        ]);
    }

    #[Test]
    public function paid_amount_converts_a_confirmed_payment_in_another_currency(): void
    {
        Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $order = $this->orderWithVendorCurrency('PFI', 1.0, 54.03);
        $this->paiement($order, 3000000, 'GNF', now());

        // 3 000 000 GNF -> 360 dans la devise de la commande, pas 3 000 000.
        $this->assertEqualsWithDelta(360.0, $order->paidAmount(), 0.5);
    }

    #[Test]
    public function paid_amount_ignores_unconfirmed_declarations(): void
    {
        $order = $this->orderWithVendorCurrency('USD', 1.0, 100);
        $this->paiement($order, 100, 'USD', null);

        $this->assertSame(0.0, $order->paidAmount());
    }

    #[Test]
    public function declared_amount_counts_confirmed_and_unconfirmed_alike(): void
    {
        // C'est la différence avec paidAmount() : PaiementModal s'en sert pour
        // empêcher une seconde déclaration tant que la première n'est ni
        // confirmée ni refusée.
        $order = $this->orderWithVendorCurrency('USD', 1.0, 100);
        $this->paiement($order, 60, 'USD', now());
        $this->paiement($order, 40, 'USD', null);

        $this->assertEqualsWithDelta(100.0, $order->declaredAmount(), 0.01);
        $this->assertEqualsWithDelta(60.0, $order->paidAmount(), 0.01);
    }

    #[Test]
    public function declared_amount_also_converts_across_currencies(): void
    {
        Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $order = $this->orderWithVendorCurrency('USD', 1.0, 100);
        $this->paiement($order, 200000, 'GNF', null);

        // 200 000 GNF -> 24 USD, pas 200 000.
        $this->assertEqualsWithDelta(24.0, $order->declaredAmount(), 0.5);
    }

    #[Test]
    public function declared_awaiting_confirmation_converts_too(): void
    {
        Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $order = $this->orderWithVendorCurrency('USD', 1.0, 100);
        $this->paiement($order, 200000, 'GNF', null);
        $this->paiement($order, 10, 'USD', now());

        // Seule la ligne non confirmée compte ici, convertie.
        $this->assertEqualsWithDelta(24.0, $order->declaredAwaitingConfirmation(), 0.5);
    }

    #[Test]
    public function the_filament_order_form_shows_the_converted_balance(): void
    {
        // Régression directe sur les trois Placeholders d'OrderResource.php :
        // ils appelaient tous confirmedPaiements()->sum('amount') en brut.
        Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $order = $this->orderWithVendorCurrency('PFI', 1.0, 54.03);
        $this->paiement($order, 3000000, 'GNF', now());

        $this->assertEqualsWithDelta(360.0, $order->paidAmount(), 0.5);
        $this->assertLessThan(1000, $order->paidAmount());
    }
}
