<?php

namespace Tests\Feature\Finance;

use App\Models\CommissionSetting;
use App\Models\Vendor;
use App\Services\FinanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La commission de la plateforme.
 *
 * Neuf boutiques sur dix n'en avaient aucune : la table ne portait qu'une ligne,
 * et calculateCommission() renvoie 0 quand rien ne correspond. La plateforme ne
 * prelevait donc rien sur presque chaque vente. Une regle globale a 5 % est
 * posee par migration, et ces tests verifient surtout ce qui l'annulerait.
 */
class PlatformCommissionTest extends TestCase
{
    use RefreshDatabase;

    private function calculator(): FinanceCalculator
    {
        return new FinanceCalculator();
    }

    /** La regle globale que la migration pose. */
    private function globalRule(): CommissionSetting
    {
        return CommissionSetting::whereNull('vendor_id')->firstOrFail();
    }

    #[Test]
    public function the_migration_leaves_a_single_global_rule_at_five_percent(): void
    {
        $rule = $this->globalRule();

        $this->assertSame('percentage', $rule->commission_type);
        $this->assertEqualsWithDelta(5, $rule->commission_rate, 0.001);
        $this->assertTrue($rule->is_active);
        $this->assertNull($rule->minimum_amount);
        $this->assertNull($rule->maximum_amount);
        $this->assertSame(1, CommissionSetting::whereNull('vendor_id')->count());
    }

    #[Test]
    public function a_shop_with_no_rule_of_its_own_pays_five_percent(): void
    {
        // Le cas de neuf boutiques sur dix, qui payaient zero.
        $vendor = Vendor::factory()->create();

        $this->assertEqualsWithDelta(
            50,
            $this->calculator()->calculateCommission(1000, $vendor->id),
            0.01
        );
    }

    #[Test]
    public function the_rate_holds_on_small_and_large_orders_alike(): void
    {
        // Ce que les bornes de l'ancienne ligne BilloStore cassaient : un
        // plancher transforme une petite vente en perte, un plafond fait chuter
        // le taux reel des que la commande grossit.
        $vendor = Vendor::factory()->create();
        $calculator = $this->calculator();

        $this->assertEqualsWithDelta(0.5, $calculator->calculateCommission(10, $vendor->id), 0.01);
        $this->assertEqualsWithDelta(5000, $calculator->calculateCommission(100000, $vendor->id), 0.01);
    }

    #[Test]
    public function a_shop_specific_rule_still_wins(): void
    {
        // La regle globale est un repli, pas un plafond : une exception reste
        // possible depuis l'admin.
        $vendor = Vendor::factory()->create();

        CommissionSetting::create([
            'vendor_id' => $vendor->id,
            'commission_type' => 'percentage',
            'commission_rate' => 12,
            'is_active' => true,
        ]);

        $this->assertEqualsWithDelta(
            120,
            $this->calculator()->calculateCommission(1000, $vendor->id),
            0.01
        );
    }

    #[Test]
    public function deactivating_a_shops_exception_falls_back_to_five_percent(): void
    {
        // Le piege corrige. Avant, la ligne de la boutique etait choisie puis
        // rejetee si inactive, et la methode renvoyait 0 sans jamais regarder la
        // regle globale : desactiver une exception dans l'admin voulait dire
        // « cette boutique ne paie plus rien ».
        $vendor = Vendor::factory()->create();

        CommissionSetting::create([
            'vendor_id' => $vendor->id,
            'commission_type' => 'percentage',
            'commission_rate' => 12,
            'is_active' => false,
        ]);

        $this->assertEqualsWithDelta(
            50,
            $this->calculator()->calculateCommission(1000, $vendor->id),
            0.01
        );
    }

    #[Test]
    public function deactivating_the_global_rule_stops_charging_anyone(): void
    {
        // L'interrupteur general, et il doit rester franc : plus de regle
        // active, plus de commission — pas un taux fantome.
        $vendor = Vendor::factory()->create();
        $this->globalRule()->update(['is_active' => false]);

        $this->assertSame(
            0.0,
            $this->calculator()->calculateCommission(1000, $vendor->id)
        );
    }

    #[Test]
    public function no_shop_is_left_without_a_rate(): void
    {
        // Ce que la migration devait garantir : plus une seule boutique a zero.
        $vendors = Vendor::factory()->count(4)->create();
        $calculator = $this->calculator();

        foreach ($vendors as $vendor) {
            $this->assertGreaterThan(
                0,
                $calculator->calculateCommission(1000, $vendor->id),
                "la boutique {$vendor->id} ne paie aucune commission"
            );
        }
    }

    #[Test]
    public function the_migration_can_be_replayed_without_stacking_rules(): void
    {
        // Elle partira en production par la page de maintenance, ou un clic de
        // trop est vite arrive.
        $this->artisan('migrate:refresh', [
            '--path' => 'database/migrations/2026_09_11_120000_set_platform_commission_to_five_percent.php',
        ])->assertExitCode(0);

        $this->assertSame(1, CommissionSetting::whereNull('vendor_id')->count());
        $this->assertEqualsWithDelta(5, $this->globalRule()->commission_rate, 0.001);
    }

    #[Test]
    public function a_clamped_shop_rule_is_flattened_and_its_old_setting_recorded(): void
    {
        // Le cas BilloStore : 5 % annonces, mais bornes entre 100 et 200.
        $vendor = Vendor::factory()->create();

        DB::table('commission_settings')->insert([
            'vendor_id' => $vendor->id,
            'commission_type' => 'percentage',
            'commission_rate' => 5,
            'minimum_amount' => 100,
            'maximum_amount' => 200,
            'payment_method' => 'stripe',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('migrate:refresh', [
            '--path' => 'database/migrations/2026_09_11_120000_set_platform_commission_to_five_percent.php',
        ])->assertExitCode(0);

        $rule = CommissionSetting::where('vendor_id', $vendor->id)->firstOrFail();

        $this->assertNull($rule->minimum_amount);
        $this->assertNull($rule->maximum_amount);
        $this->assertStringContainsString('minimum 100', $rule->notes);
        $this->assertStringContainsString('maximum 200', $rule->notes);

        // Et le taux tient desormais sur une petite commande.
        $this->assertEqualsWithDelta(
            0.5,
            $this->calculator()->calculateCommission(10, $vendor->id),
            0.01
        );
    }
}
