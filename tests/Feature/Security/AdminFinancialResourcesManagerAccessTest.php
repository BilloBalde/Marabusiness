<?php

namespace Tests\Feature\Security;

use App\Filament\Resources\FinancialTransactionResource\Pages\ListFinancialTransactions;
use App\Filament\Resources\PaiementResource;
use App\Filament\Resources\ShippingCarrierRateResource\Pages\ListShippingCarrierRates;
use App\Filament\Resources\ShippingZoneResource\Pages\ListShippingZones;
use App\Filament\Resources\VendorPayoutResource;
use App\Models\Order;
use App\Models\Paiement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayout;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * All 8 resources across this file and ManagerFinancialAccessTest now share
 * App\Filament\Concerns\ManagerReadOnly: a manager keeps the view access the
 * owner asked to preserve, but canCreate()/canEdit()/canDelete()/canDeleteAny()
 * are 'admin'-only. Before this, none of the eight had any authorization
 * override, and a manager could rewrite the ledger and payout status outright,
 * not just see the rate config that produces them.
 */
class AdminFinancialResourcesManagerAccessTest extends TestCase
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
    public function a_manager_can_still_open_the_financial_transaction_ledger(): void
    {
        $this->actingAsAdminPanel($this->manager());

        Livewire::test(ListFinancialTransactions::class)->assertSuccessful();
    }

    #[Test]
    public function a_manager_can_no_longer_mark_a_vendor_payout_completed(): void
    {
        $vendor = Vendor::factory()->create();

        $payout = VendorPayout::create([
            'vendor_id' => $vendor->id,
            'amount' => 500,
            'commission_amount' => 50,
            'gateway_fees' => 10,
            'wire_fees' => 5,
            'net_amount' => 435,
            'payout_method' => 'bank_wire',
            'status' => VendorPayout::STATUS_PENDING,
            'reference_number' => 'PAYOUT-TEST-' . uniqid(),
        ]);

        $this->actingAsAdminPanel($this->manager());

        $this->get(VendorPayoutResource::getUrl('edit', ['record' => $payout]))
            ->assertForbidden();

        $this->assertSame(VendorPayout::STATUS_PENDING, $payout->fresh()->status);
    }

    #[Test]
    public function a_manager_can_no_longer_mark_an_unconfirmed_payment_as_paid(): void
    {
        $vendor = Vendor::factory()->create();
        $order = Order::factory()->create(['vendor_id' => $vendor->id]);

        $paiement = Paiement::create([
            'order_id' => $order->id,
            'amount' => 100,
            'currency' => 'usd',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'transaction_id' => 'TEST-' . uniqid(),
            'confirmed_at' => null,
        ]);

        $this->actingAsAdminPanel($this->manager());

        $this->get(PaiementResource::getUrl('edit', ['record' => $paiement]))
            ->assertForbidden();

        $this->assertSame('pending', $paiement->fresh()->payment_status);
    }

    #[Test]
    public function a_manager_can_still_open_every_vendors_shipping_carrier_rates(): void
    {
        // Read-only view is fine here too — the fix is that edits/creates/deletes are
        // now 'admin'-only, not that a manager loses visibility entirely.
        $this->actingAsAdminPanel($this->manager());

        Livewire::test(ListShippingCarrierRates::class)->assertSuccessful();
        Livewire::test(ListShippingZones::class)->assertSuccessful();
    }
}
