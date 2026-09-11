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
 * Order::syncPaymentTotals() additionnait paiements.amount tel quel, sans
 * jamais convertir vers la devise de la commande — alors que paiements.currency
 * est une vraie colonne, indépendante, que rien n'oblige à correspondre.
 *
 * Sur les données réelles de ce dépôt, une commande à 54,03 (dans sa propre
 * devise) porte un paiement confirmé de 3 000 000 GNF : total_paid dépassait
 * grand_total d'un facteur de plusieurs dizaines de milliers, et la commande
 * se lisait « payée » quel que soit ce qui était réellement arrivé.
 */
class CrossCurrencyPaymentTotalsTest extends TestCase
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

    #[Test]
    public function a_payment_in_a_different_currency_is_converted_before_being_counted(): void
    {
        // La commande précise : devise à 54,03 (rate_to_usd = 1, proche de
        // l'USD), un paiement confirmé de 3 000 000 GNF (rate_to_usd = 0.00012,
        // donc 360 dans la devise de la commande) — payé en entier, pas
        // 3 000 000 fois le total.
        Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $order = $this->orderWithVendorCurrency('PFI', 1.0, 54.03);

        Paiement::create([
            'order_id' => $order->id,
            'amount' => 3000000,
            'currency' => 'GNF',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'transaction_id' => 'TEST-' . uniqid(),
            'confirmed_at' => now(),
        ]);

        $order->syncPaymentTotals();
        $order->refresh();

        $this->assertEqualsWithDelta(360.0, $order->total_paid, 0.5);
        $this->assertSame('paid', $order->payment_status);
        $this->assertEqualsWithDelta(0.0, $order->total_remaining, 0.5);

        // Ce que le bug produisait réellement : total_paid = 3 000 000, un
        // chiffre qui n'a physiquement aucun sens pour cette commande.
        $this->assertLessThan(1000, $order->total_paid);
    }

    #[Test]
    public function a_payment_in_the_orders_own_currency_needs_no_lookup_and_is_unaffected(): void
    {
        // Le cas courant, qui doit rester exactement comme avant.
        $order = $this->orderWithVendorCurrency('GNF', 0.00012, 200000);

        Paiement::create([
            'order_id' => $order->id,
            'amount' => 200000,
            'currency' => 'GNF',
            'payment_method' => 'om',
            'payment_status' => 'paid',
            'transaction_id' => 'TEST-' . uniqid(),
            'confirmed_at' => now(),
        ]);

        $order->syncPaymentTotals();
        $order->refresh();

        $this->assertEqualsWithDelta(200000, $order->total_paid, 0.01);
        $this->assertSame('paid', $order->payment_status);
    }

    #[Test]
    public function an_unresolvable_payment_currency_passes_the_amount_through_rather_than_dropping_it(): void
    {
        // Une devise introuvable (renommée, supprimée, faute de frappe sur une
        // saisie manuelle) ne doit pas faire disparaître le paiement du
        // solde — Money::convert() le laisse passer tel quel, comme pour un
        // taux nul ou absent.
        $order = $this->orderWithVendorCurrency('PFI', 1.0, 100);

        Paiement::create([
            'order_id' => $order->id,
            'amount' => 100,
            'currency' => 'XYZ',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'transaction_id' => 'TEST-' . uniqid(),
            'confirmed_at' => now(),
        ]);

        $order->syncPaymentTotals();
        $order->refresh();

        $this->assertEqualsWithDelta(100, $order->total_paid, 0.01);
    }

    #[Test]
    public function several_payments_in_different_currencies_are_each_converted_and_summed(): void
    {
        Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        Currency::factory()->create(['code' => 'EUR', 'rate_to_usd' => 1.08]);
        $order = $this->orderWithVendorCurrency('USD', 1.0, 500);

        // 200 000 GNF -> 24 USD, plus 200 EUR -> 216 USD : 240 au total.
        Paiement::create([
            'order_id' => $order->id, 'amount' => 200000, 'currency' => 'GNF',
            'payment_method' => 'cash', 'payment_status' => 'partial',
            'transaction_id' => 'TEST-' . uniqid(), 'confirmed_at' => now(),
        ]);
        Paiement::create([
            'order_id' => $order->id, 'amount' => 200, 'currency' => 'EUR',
            'payment_method' => 'cash', 'payment_status' => 'partial',
            'transaction_id' => 'TEST-' . uniqid(), 'confirmed_at' => now(),
        ]);

        $order->syncPaymentTotals();
        $order->refresh();

        $this->assertEqualsWithDelta(240.0, $order->total_paid, 1.0);
        $this->assertSame('partial', $order->payment_status);
    }

    #[Test]
    public function a_lowercase_currency_code_is_treated_as_the_same_currency(): void
    {
        // Cas réel de ce dépôt : une commande porte des paiements 'GNF' et
        // 'gnf' pour la même devise — une saisie manuelle jamais normalisée.
        // La comparaison insensible à la casse doit prendre le chemin rapide
        // (même devise que la commande), pas rater le rapprochement.
        $order = $this->orderWithVendorCurrency('GNF', 0.00012, 200000);

        Paiement::create([
            'order_id' => $order->id,
            'amount' => 200000,
            'currency' => 'gnf',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'transaction_id' => 'TEST-' . uniqid(),
            'confirmed_at' => now(),
        ]);

        $order->syncPaymentTotals();
        $order->refresh();

        $this->assertEqualsWithDelta(200000, $order->total_paid, 0.01);
        $this->assertSame('paid', $order->payment_status);
    }

    #[Test]
    public function an_unconfirmed_payment_in_another_currency_still_does_not_count(): void
    {
        // La conversion ne doit pas devenir une porte dérobée qui compte une
        // déclaration jamais confirmée.
        $order = $this->orderWithVendorCurrency('PFI', 1.0, 100);

        Paiement::create([
            'order_id' => $order->id,
            'amount' => 999999,
            'currency' => 'GNF',
            'payment_method' => 'om',
            'payment_status' => 'paid',
            'transaction_id' => 'TEST-' . uniqid(),
            'confirmed_at' => null,
        ]);

        $order->syncPaymentTotals();
        $order->refresh();

        $this->assertEqualsWithDelta(0.0, (float) $order->total_paid, 0.01);
        $this->assertSame('pending', $order->payment_status);
    }
}
