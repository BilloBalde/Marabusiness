<?php

namespace Tests\Feature\Sourcing;

use App\Filament\Resources\SourcingResource\Pages\CreateSourcing;
use App\Filament\Resources\SourcingResource\Pages\EditSourcing;
use App\Filament\Resources\SourcingResource\Pages\ListSourcings;
use App\Models\Sourcing;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SourcingResourceSmokeTest extends TestCase
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

        Livewire::test(ListSourcings::class)->assertSuccessful();
    }

    #[Test]
    public function a_sourcing_can_be_created_with_only_french_filled_in(): void
    {
        $this->actingAsAdminPanel($this->admin());

        Livewire::test(CreateSourcing::class)
            ->fillForm([
                'slug' => 'communication',
                'title.fr' => 'Support de communication',
                'short_description.fr' => 'Casquettes, t-shirts, cups, clés USB personnalisées.',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $sourcing = Sourcing::where('slug', 'communication')->first();

        $this->assertNotNull($sourcing);
        $this->assertSame('Support de communication', $sourcing->title['fr']);
        $this->assertNull($sourcing->title['en'] ?? null, 'english should stay empty for now, not fabricated');
        $this->assertSame('Support de communication', $sourcing->title_text);
    }

    #[Test]
    public function the_edit_page_renders_and_the_relation_manager_is_reachable(): void
    {
        $sourcing = Sourcing::create([
            'slug' => 'playgrounds',
            'title' => ['fr' => 'Aires de jeux'],
            'short_description' => ['fr' => 'Playgrounds pour enfants'],
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->actingAsAdminPanel($this->admin());

        Livewire::test(EditSourcing::class, ['record' => $sourcing->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(['slug' => 'playgrounds', 'title.fr' => 'Aires de jeux']);
    }
}
