<?php

namespace Tests\Feature\Payment;

use App\Models\Currency;
use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 141 commandes sur 187, sur ce dépôt, portaient un rate_to_usd NULL — pas
 * seulement d'anciennes, certaines vieilles de quelques mois. Sans lui,
 * Order::syncPaymentTotals() n'a rien vers quoi convertir un paiement dans
 * une autre devise, et Money::convert() laisse le montant passer tel quel.
 *
 * Le code de création actuel pose déjà ce champ correctement ; cette
 * migration ne fait que combler la dette laissée par ce qui existait avant.
 *
 * La migration est instanciée et appelée directement (require + up()) plutôt
 * que via $this->artisan('migrate', …) : sous RefreshDatabase, un artisan
 * migrate lancé au milieu d'un test rouvre une migration au sein d'une
 * transaction déjà ouverte par le test lui-même, ce que SQLite gère mal — la
 * commande rendait "Nothing to migrate" ou ne persistait rien selon les
 * essais. Appeler up() directement s'exécute dans la même transaction que le
 * reste du test, sans ce risque.
 */
class BackfillOrderRateToUsdTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): Migration
    {
        return require base_path('database/migrations/2026_09_12_090000_backfill_missing_order_rate_to_usd.php');
    }

    #[Test]
    public function an_order_missing_its_rate_is_backfilled_from_its_vendors_currency(): void
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id]);
        $order = Order::factory()->create(['vendor_id' => $vendor->id]);

        DB::table('orders')->where('id', $order->id)->update(['rate_to_usd' => null]);

        $this->migration()->up();

        $this->assertEqualsWithDelta(0.00012, (float) $order->fresh()->rate_to_usd, 0.000001);
    }

    #[Test]
    public function an_order_that_already_has_a_rate_is_left_alone(): void
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id]);
        $order = Order::factory()->create(['vendor_id' => $vendor->id, 'rate_to_usd' => 1.0]);

        $this->migration()->up();

        // Le taux d'origine tient — pas celui, différent, de la devise
        // actuelle du vendeur. Cette migration comble une absence, elle ne
        // corrige pas une valeur déjà présente.
        $this->assertEqualsWithDelta(1.0, (float) $order->fresh()->rate_to_usd, 0.000001);
    }

    #[Test]
    public function an_order_with_no_vendor_at_all_is_left_null(): void
    {
        // 31 commandes de ce dépôt n'ont pas de vendeur — aucune devise à
        // retrouver pour elles, donc rien à deviner.
        $order = Order::factory()->create(['vendor_id' => null, 'rate_to_usd' => null]);

        $this->migration()->up();

        $this->assertNull($order->fresh()->rate_to_usd);
    }

    #[Test]
    public function running_it_twice_does_not_overwrite_an_already_backfilled_order(): void
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id]);
        $order = Order::factory()->create(['vendor_id' => $vendor->id]);
        DB::table('orders')->where('id', $order->id)->update(['rate_to_usd' => null]);

        $migration = $this->migration();
        $migration->up();
        $firstPass = $order->fresh()->rate_to_usd;

        // Le taux de la devise change après coup — un second passage ne doit
        // pas revenir écraser une commande déjà comblée avec une valeur plus
        // récente, potentiellement fausse pour l'époque de la commande :
        // whereNull('rate_to_usd') l'exclut désormais.
        $currency->update(['rate_to_usd' => 0.0002]);

        $migration->up();

        $this->assertEqualsWithDelta($firstPass, (float) $order->fresh()->rate_to_usd, 0.000001);
    }
}
