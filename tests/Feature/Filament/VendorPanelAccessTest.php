<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use App\Models\Vendor;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Applying to become a vendor (web page and public API alike) assigns the
 * 'vendor' role on the spot, with the shop created is_active=false and
 * approved_at=null. canAccessPanel() only asked for the role and a vendor row,
 * so an applicant got the whole vendor panel — products, orders, everything —
 * before anyone approved them. Combined with a storefront that did not filter
 * on is_active, the approval step protected nothing.
 */
class VendorPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    private function vendorUser(bool $active): User
    {
        Role::findOrCreate('vendor', 'web');

        $user = User::factory()->create();
        $user->assignRole('vendor');

        Vendor::factory()->create([
            'user_id' => $user->id,
            'is_active' => $active,
            'approved_at' => $active ? now() : null,
        ]);

        return $user;
    }

    #[Test]
    public function an_approved_vendor_can_enter_the_vendor_panel(): void
    {
        $user = $this->vendorUser(active: true);

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('vendor')));
    }

    #[Test]
    public function a_vendor_still_awaiting_approval_cannot(): void
    {
        $user = $this->vendorUser(active: false);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('vendor')));
    }

    #[Test]
    public function a_vendor_who_is_deactivated_later_loses_access(): void
    {
        $user = $this->vendorUser(active: true);
        $user->vendor()->update(['is_active' => false]);

        $this->assertFalse($user->fresh()->canAccessPanel(Filament::getPanel('vendor')));
    }

    #[Test]
    public function staff_are_not_affected(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('admin')));
    }
}
