<?php

namespace Tests\Feature\Security;

use App\Filament\Pages\ChatPage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * ChatPage::mount() and loadCustomers() branch on the manager, vendor and
 * customer roles and have no else. A user holding only 'admin' fell through all
 * of them, left $customers null, and the view's @forelse threw
 * "foreach() argument must be of type array|object, null given" — a 500 for
 * every administrator who opened their own back office.
 */
class ChatPageResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'manager', 'vendor', 'customer'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    #[Test]
    public function an_admin_can_open_the_chat_page(): void
    {
        $this->actingAs(User::factory()->create()->assignRole('admin'));

        Livewire::test(ChatPage::class)->assertOk();
    }

    #[Test]
    public function an_admin_sees_an_empty_list_rather_than_an_error(): void
    {
        // An admin holds no conversations of their own. Saying so is the honest
        // answer; crashing is not.
        $this->actingAs(User::factory()->create()->assignRole('admin'));

        $customers = Livewire::test(ChatPage::class)->get('customers');

        $this->assertNotNull($customers);
        $this->assertCount(0, $customers);
    }

    #[Test]
    public function searching_from_an_admin_account_does_not_crash_either(): void
    {
        // loadCustomers() has the same missing else as mount().
        $this->actingAs(User::factory()->create()->assignRole('admin'));

        Livewire::test(ChatPage::class)
            ->set('searchTerm', 'quelqu un')
            ->call('loadCustomers')
            ->assertOk();
    }

    #[Test]
    public function a_manager_still_sees_the_customers(): void
    {
        // The roles that already worked must keep working.
        $manager = User::factory()->create()->assignRole('manager');
        User::factory()->create()->assignRole('customer');

        $this->actingAs($manager);

        $this->assertCount(1, Livewire::test(ChatPage::class)->get('customers'));
    }
}
