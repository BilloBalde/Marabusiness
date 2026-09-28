<?php

namespace Tests\Feature\Home;

use App\Filament\Resources\SettingResource\Pages\CreateSetting;
use App\Filament\Resources\SettingResource\Pages\ListSettings;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingResourceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function actingAsAdminPanel(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
    }

    #[Test]
    public function the_list_page_renders(): void
    {
        $this->actingAsAdminPanel($this->admin());

        Livewire::test(ListSettings::class)->assertSuccessful();
    }

    #[Test]
    public function a_setting_can_be_created_and_read_back_through_the_model_helper(): void
    {
        $this->actingAsAdminPanel($this->admin());

        Livewire::test(CreateSetting::class)
            ->fillForm([
                'key' => 'sourcing_intro',
                'value.fr' => 'Notre savoir-faire en sourcing.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('Notre savoir-faire en sourcing.', Setting::text('sourcing_intro'));
    }

    #[Test]
    public function a_duplicate_key_is_rejected(): void
    {
        Setting::create(['key' => 'sourcing_intro', 'value' => ['fr' => 'Déjà là.']]);

        $this->actingAsAdminPanel($this->admin());

        Livewire::test(CreateSetting::class)
            ->fillForm([
                'key' => 'sourcing_intro',
                'value.fr' => 'Nouvelle tentative.',
            ])
            ->call('create')
            ->assertHasFormErrors(['key' => 'unique']);

        $this->assertSame('Déjà là.', Setting::text('sourcing_intro'));
    }
}
