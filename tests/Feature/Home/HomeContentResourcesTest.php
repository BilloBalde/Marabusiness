<?php

namespace Tests\Feature\Home;

use App\Filament\Resources\HomeSlideResource\Pages\ListHomeSlides;
use App\Filament\Resources\PartnerResource\Pages\ListPartners;
use App\Filament\Resources\TestimonialResource\Pages\ListTestimonials;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HomeContentResourcesTest extends TestCase
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
    public function the_testimonials_list_page_renders(): void
    {
        $this->actingAsAdminPanel($this->admin());

        Livewire::test(ListTestimonials::class)->assertSuccessful();
    }

    #[Test]
    public function the_partners_list_page_renders(): void
    {
        $this->actingAsAdminPanel($this->admin());

        Livewire::test(ListPartners::class)->assertSuccessful();
    }

    #[Test]
    public function the_home_slides_list_page_renders(): void
    {
        $this->actingAsAdminPanel($this->admin());

        Livewire::test(ListHomeSlides::class)->assertSuccessful();
    }

    #[Test]
    public function a_testimonial_can_be_created_with_only_french_filled_in(): void
    {
        $this->actingAsAdminPanel($this->admin());

        Livewire::test(\App\Filament\Resources\TestimonialResource\Pages\CreateTestimonial::class)
            ->fillForm([
                'name' => 'Aïssatou Camara',
                'role' => 'Étudiante en France',
                'message.fr' => 'Le suivi était excellent du dossier jusqu\'au visa.',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $testimonial = \App\Models\Testimonial::where('name', 'Aïssatou Camara')->first();

        $this->assertNotNull($testimonial);
        $this->assertSame('Le suivi était excellent du dossier jusqu\'au visa.', $testimonial->message_text);
    }
}
