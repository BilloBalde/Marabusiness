<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A manager looks after customers and vendors; they are not meant to reach the admin
 * role. UserResource already hides admin accounts from a manager's list and refuses to
 * open one. What it did NOT do is check the role a manager submits: the role field is
 * not a column, EditUser/CreateUser read it back from the raw form state, and Filament's
 * Select does not turn its options into a validation rule. A crafted request could set
 * role = "admin" on any customer — and a manager can also set that customer's password.
 */
class StaffPrivilegeBoundariesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        foreach (['admin', 'manager', 'vendor', 'customer'] as $name) {
            Role::findOrCreate($name, 'web');
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function actAs(User $user): void
    {
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    // --- the role a manager may hand out -------------------------------------------------

    #[Test]
    public function a_manager_cannot_promote_a_customer_to_admin(): void
    {
        $customer = $this->user('customer');
        $this->actAs($this->user('manager'));

        Livewire::test(EditUser::class, ['record' => $customer->getKey()])
            ->fillForm(['role' => 'admin'])
            ->call('save');

        $this->assertFalse($customer->fresh()->hasRole('admin'));
        $this->assertTrue($customer->fresh()->hasRole('customer'));
    }

    #[Test]
    public function a_manager_cannot_create_an_admin_account(): void
    {
        $this->actAs($this->user('manager'));

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Nouvel admin',
                'email' => 'nouvel.admin@example.test',
                'password' => 'un-mot-de-passe-solide',
                'password_confirmation' => 'un-mot-de-passe-solide',
                'role' => 'admin',
            ])
            ->call('create');

        $created = User::where('email', 'nouvel.admin@example.test')->first();

        $this->assertTrue($created === null || ! $created->hasRole('admin'));
    }

    #[Test]
    public function a_manager_cannot_grant_the_manager_role_either(): void
    {
        $vendor = $this->user('vendor');
        $this->actAs($this->user('manager'));

        Livewire::test(EditUser::class, ['record' => $vendor->getKey()])
            ->fillForm(['role' => 'manager'])
            ->call('save');

        $this->assertFalse($vendor->fresh()->hasRole('manager'));
    }

    #[Test]
    public function a_manager_can_still_move_an_account_between_customer_and_vendor(): void
    {
        $customer = $this->user('customer');
        $this->actAs($this->user('manager'));

        Livewire::test(EditUser::class, ['record' => $customer->getKey()])
            ->fillForm(['role' => 'vendor'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($customer->fresh()->hasRole('vendor'));
    }

    #[Test]
    public function an_admin_can_hand_out_any_role(): void
    {
        $customer = $this->user('customer');
        $this->actAs($this->user('admin'));

        Livewire::test(EditUser::class, ['record' => $customer->getKey()])
            ->fillForm(['role' => 'manager'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($customer->fresh()->hasRole('manager'));
    }

    // --- what a manager can see and open (already protected: kept that way) ---------------

    #[Test]
    public function an_admin_account_is_invisible_to_a_manager(): void
    {
        $admin = $this->user('admin');
        $customer = $this->user('customer');
        $this->actAs($this->user('manager'));

        $visible = UserResource::getEloquentQuery()->pluck('id');

        $this->assertFalse($visible->contains($admin->id));
        $this->assertTrue($visible->contains($customer->id));
    }

    #[Test]
    public function a_manager_cannot_open_an_admins_edit_page(): void
    {
        $admin = $this->user('admin');
        $this->actAs($this->user('manager'));

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(EditUser::class, ['record' => $admin->getKey()]);
    }
}
