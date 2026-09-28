<?php

namespace Tests\Feature\Security;

use App\Filament\Resources\CommissionSettingResource;
use App\Filament\Resources\CommissionSettingResource\Pages\ListCommissionSettings;
use App\Filament\Resources\PaymentGatewayFeeResource;
use App\Filament\Resources\PaymentGatewayFeeResource\Pages\ListPaymentGatewayFees;
use App\Filament\Resources\WireTransferFeeResource;
use App\Filament\Resources\WireTransferFeeResource\Pages\ListWireTransferFees;
use App\Models\CommissionSetting;
use App\Models\PaymentGatewayFee;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WireTransferFee;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A manager still needs to see these — that was the owner's call once asked —
 * but no longer to change them. CommissionSettingResource, PaymentGatewayFeeResource
 * and WireTransferFeeResource now share App\Filament\Concerns\ManagerReadOnly,
 * which restricts canCreate()/canEdit()/canDelete()/canDeleteAny() to 'admin'; each
 * Resource also declares canViewAny() explicitly (admin or manager). Before this,
 * none of the three had any authorization override at all.
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
    public function a_manager_can_still_open_the_commission_settings_list(): void
    {
        $this->actingAsAdminPanel($this->manager());

        Livewire::test(ListCommissionSettings::class)->assertSuccessful();
    }

    #[Test]
    public function a_manager_can_still_open_the_payment_gateway_fee_list(): void
    {
        $this->actingAsAdminPanel($this->manager());

        Livewire::test(ListPaymentGatewayFees::class)->assertSuccessful();
    }

    #[Test]
    public function a_manager_can_still_open_the_wire_transfer_fee_list(): void
    {
        $this->actingAsAdminPanel($this->manager());

        Livewire::test(ListWireTransferFees::class)->assertSuccessful();
    }

    #[Test]
    public function a_manager_can_no_longer_edit_a_commission_setting(): void
    {
        $setting = CommissionSetting::create([
            'vendor_id' => Vendor::factory()->create()->id,
            'commission_type' => 'percentage',
            'commission_rate' => 5,
        ]);

        $this->actingAsAdminPanel($this->manager());

        $this->get(CommissionSettingResource::getUrl('edit', ['record' => $setting]))
            ->assertForbidden();
    }

    #[Test]
    public function a_manager_can_no_longer_edit_a_payment_gateway_fee(): void
    {
        $fee = PaymentGatewayFee::first() ?? PaymentGatewayFee::create([
            'gateway_name' => 'stripe',
            'percentage_fee' => 2.9,
            'fixed_fee' => 0.30,
        ]);

        $this->actingAsAdminPanel($this->manager());

        $this->get(PaymentGatewayFeeResource::getUrl('edit', ['record' => $fee]))
            ->assertForbidden();
    }

    #[Test]
    public function a_manager_can_no_longer_edit_a_wire_transfer_fee(): void
    {
        $fee = WireTransferFee::first() ?? WireTransferFee::create([
            'country' => 'Guinea',
            'percentage_fee' => 1,
            'fixed_fee' => 2,
        ]);

        $this->actingAsAdminPanel($this->manager());

        $this->get(WireTransferFeeResource::getUrl('edit', ['record' => $fee]))
            ->assertForbidden();
    }
}
