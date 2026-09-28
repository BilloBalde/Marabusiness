<?php

namespace Tests\Feature\Home;

use App\Livewire\HomePage;
use App\Models\HomeSlide;
use App\Models\Partner;
use App\Models\SourceProduct;
use App\Models\Sourcing;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomePageNewSectionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_home_page_renders_with_none_of_the_new_sections_populated(): void
    {
        // Nothing seeded: hero/testimonials/partners must not break the page,
        // they just shouldn't render anything.
        Livewire::test(HomePage::class)
            ->assertSuccessful()
            ->assertDontSee('Nos Sourcing')
            ->assertDontSee('Ce que nos clients disent')
            ->assertDontSee('Ils nous font confiance');
    }

    #[Test]
    public function the_sourcing_cards_section_shows_active_sourcings_only(): void
    {
        Sourcing::create([
            'slug' => 'communication',
            'title' => ['fr' => 'Support de communication'],
            'short_description' => ['fr' => '...'],
            'is_active' => true,
        ]);
        Sourcing::create([
            'slug' => 'hidden',
            'title' => ['fr' => 'Caché'],
            'short_description' => ['fr' => '...'],
            'is_active' => false,
        ]);

        Livewire::test(HomePage::class)
            ->assertSee('Nos Sourcing')
            ->assertSee('Support de communication')
            ->assertDontSee('Caché');
    }

    #[Test]
    public function the_sourcing_card_shows_its_product_count(): void
    {
        $sourcing = Sourcing::create([
            'slug' => 'communication',
            'title' => ['fr' => 'Support de communication'],
            'short_description' => ['fr' => '...'],
            'is_active' => true,
        ]);
        SourceProduct::create([
            'sourcing_id' => $sourcing->id,
            'slug' => 'casquette',
            'name' => ['fr' => 'Casquette'],
            'is_active' => true,
        ]);

        Livewire::test(HomePage::class)->assertSee('1 produit(s)');
    }

    #[Test]
    public function the_hero_carousel_shows_only_active_slides(): void
    {
        HomeSlide::create([
            'image' => 'home-slides/one.jpg',
            'title' => ['fr' => 'Votre partenaire sourcing'],
            'is_active' => true,
            'sort_order' => 1,
        ]);
        HomeSlide::create([
            'image' => 'home-slides/two.jpg',
            'title' => ['fr' => 'Slide inactive'],
            'is_active' => false,
            'sort_order' => 2,
        ]);

        Livewire::test(HomePage::class)
            ->assertSee('Votre partenaire sourcing')
            ->assertDontSee('Slide inactive');
    }

    #[Test]
    public function the_testimonials_section_shows_active_testimonials(): void
    {
        Testimonial::create([
            'name' => 'Fatoumata Bah',
            'message' => ['fr' => 'Un accompagnement sérieux du début à la fin.'],
            'is_active' => true,
        ]);
        Testimonial::create([
            'name' => 'Masquée',
            'message' => ['fr' => '...'],
            'is_active' => false,
        ]);

        Livewire::test(HomePage::class)
            ->assertSee('Ce que nos clients disent')
            ->assertSee('Fatoumata Bah')
            ->assertSee('Un accompagnement sérieux du début à la fin.')
            ->assertDontSee('Masquée')
            // The navbar's "Testimonials" link points at /#testimonials —
            // without this id, that link would silently do nothing.
            ->assertSeeHtml('id="testimonials"');
    }

    #[Test]
    public function the_partners_section_shows_active_partners_only(): void
    {
        Partner::create([
            'name' => 'Partenaire Actif',
            'logo' => 'partners/one.png',
            'is_active' => true,
        ]);
        Partner::create([
            'name' => 'Partenaire Masqué',
            'logo' => 'partners/two.png',
            'is_active' => false,
        ]);

        Livewire::test(HomePage::class)
            ->assertSee('Ils nous font confiance')
            ->assertSee('Partenaire Actif')
            ->assertDontSee('Partenaire Masqué');
    }

    #[Test]
    public function the_contact_cta_always_shows(): void
    {
        Livewire::test(HomePage::class)->assertSee('Un projet à concrétiser ?');
    }
}
