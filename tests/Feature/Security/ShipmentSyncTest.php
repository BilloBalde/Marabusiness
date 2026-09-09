<?php

namespace Tests\Feature\Security;

use App\Filament\Resources\ShipmentResource\Pages\ListShipments;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * shipments:sync existed and worked but was never scheduled anywhere — every
 * shipment's tracking status sat frozen at whatever it was when created.
 * Render's persistent disk (where the sqlite database lives) is bound to the
 * one web service, so a separate Cron Job service couldn't reach the same
 * database file to sync into — a manual admin action was added instead of a
 * schedule that would run against an empty, unrelated database.
 *
 * Also covers a real credential leak found while verifying this:
 * CarrierTrackingService::sync() logged the DHL api_key/api_secret in plain
 * text on every single call — a debug statement ("Check what credentials
 * you're using") that made it into the code actually running in production.
 */
class ShipmentSyncTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_dhl_credentials_are_never_logged(): void
    {
        $source = file_get_contents(app_path('Services/Shipping/CarrierTrackingService.php'));

        $this->assertStringNotContainsString('dhl.api_key', $source);
        $this->assertStringNotContainsString('dhl.api_secret', $source);
        $this->assertStringNotContainsString('DHL Credentials Check', $source);
    }

    #[Test]
    public function an_admin_can_trigger_shipment_sync_manually(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(ListShipments::class)
            ->callAction('syncShipments')
            ->assertNotified();
    }

    #[Test]
    public function shipments_sync_is_scheduled_every_thirty_minutes(): void
    {
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);

        $matching = collect($schedule->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'shipments:sync'));

        $this->assertNotEmpty($matching, 'shipments:sync is not registered on the schedule.');
        $this->assertSame('*/30 * * * *', $matching->first()->expression);
    }
}
