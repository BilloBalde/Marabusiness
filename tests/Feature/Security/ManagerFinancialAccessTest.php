<?php

namespace Tests\Feature\Security;

use App\Filament\Resources\CommissionSettingResource\Pages\ListCommissionSettings;
use App\Filament\Resources\PaymentGatewayFeeResource\Pages\ListPaymentGatewayFees;
use App\Filament\Resources\WireTransferFeeResource\Pages\ListWireTransferFees;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Ce que l'audit a trouvé en lisant le code, vérifié en le reproduisant : aucune
 * classe Policy n'existe dans tout le dépôt (`app/Policies/` est absent), et la
 * quasi-totalité des Resources du panel admin n'a ni canViewAny() ni
 * getEloquentQuery() propre. Filament ouvre par défaut une Resource à quiconque
 * franchit la porte du panel quand ni policy ni override n'existe — et
 * `User::canAccessPanel()` laisse entrer 'admin' ET 'manager' indifféremment
 * sur le panel par défaut.
 *
 * UserResource, lui, restreint correctement un manager (vérifié séparément) —
 * ce test montre que ce soin ne s'est pas étendu aux ressources financières.
 */
class ManagerFinancialAccessTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        Role::findOrCreate('manager', 'web');
        $user = User::factory()->create();
        $user->assignRole('manager');

        return $user;
    }

    private function actingAsAdminPanel(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
    }

    #[Test]
    public function a_manager_can_open_the_commission_settings_list(): void
    {
        // Le taux de commission de la plateforme sur chaque vente. Aucune
        // restriction ne l'empêche.
        $this->actingAsAdminPanel($this->manager());

        Livewire::test(ListCommissionSettings::class)->assertSuccessful();
    }

    #[Test]
    public function a_manager_can_open_the_payment_gateway_fee_list(): void
    {
        $this->actingAsAdminPanel($this->manager());

        Livewire::test(ListPaymentGatewayFees::class)->assertSuccessful();
    }

    #[Test]
    public function a_manager_can_open_the_wire_transfer_fee_list(): void
    {
        $this->actingAsAdminPanel($this->manager());

        Livewire::test(ListWireTransferFees::class)->assertSuccessful();
    }
}
