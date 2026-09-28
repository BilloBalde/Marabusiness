<?php

namespace Tests\Feature\Sourcing;

use App\Livewire\SourcingDetailPage;
use App\Livewire\SourcingQuotePage;
use App\Livewire\SourcingsPage;
use App\Models\Message;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\SourceProduct;
use App\Models\Sourcing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SourcingPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function sourcingWithProduct(): array
    {
        $sourcing = Sourcing::create([
            'slug' => 'communication',
            'title' => ['fr' => 'Support de communication'],
            'short_description' => ['fr' => 'Casquettes, t-shirts, cups, clés USB.'],
            'is_active' => true,
        ]);

        $product = SourceProduct::create([
            'sourcing_id' => $sourcing->id,
            'slug' => 'casquette-brodee',
            'name' => ['fr' => 'Casquette brodée'],
            'price_range' => '50 000–80 000 GNF',
            'quantity_minimum' => 100,
            'is_active' => true,
        ]);

        return [$sourcing, $product];
    }

    #[Test]
    public function the_listing_page_shows_active_sourcings_and_the_intro(): void
    {
        [$sourcing] = $this->sourcingWithProduct();
        Setting::create(['key' => 'sourcing_intro', 'value' => ['fr' => 'Notre savoir-faire en sourcing.']]);

        Livewire::test(SourcingsPage::class)
            ->assertSee('Support de communication')
            ->assertSee('Notre savoir-faire en sourcing.');
    }

    #[Test]
    public function an_inactive_sourcing_is_not_listed(): void
    {
        Sourcing::create([
            'slug' => 'hidden',
            'title' => ['fr' => 'Caché'],
            'short_description' => ['fr' => '...'],
            'is_active' => false,
        ]);

        Livewire::test(SourcingsPage::class)->assertDontSee('Caché');
    }

    #[Test]
    public function the_detail_page_shows_the_sourcing_and_its_product(): void
    {
        [$sourcing, $product] = $this->sourcingWithProduct();

        Livewire::test(SourcingDetailPage::class, ['sourcing' => $sourcing->slug])
            ->assertSee('Support de communication')
            ->assertSee('Casquette brodée')
            ->assertSee('50 000–80 000 GNF');
    }

    #[Test]
    public function selecting_a_product_while_logged_out_redirects_to_login_and_does_not_count_interest(): void
    {
        [$sourcing, $product] = $this->sourcingWithProduct();

        Livewire::test(SourcingDetailPage::class, ['sourcing' => $sourcing->slug])
            ->call('selectProduct', $product->id)
            ->assertRedirect(route('login', ['redirect' => route('sourcing.quote', [
                'sourcing' => $sourcing->slug,
                'sourceProduct' => $product->slug,
            ])]));

        $this->assertSame(0, $product->fresh()->interest_count);
    }

    #[Test]
    public function selecting_a_product_while_logged_in_redirects_to_the_quote_form_and_counts_interest(): void
    {
        [$sourcing, $product] = $this->sourcingWithProduct();
        $this->actingAs(User::factory()->create());

        Livewire::test(SourcingDetailPage::class, ['sourcing' => $sourcing->slug])
            ->call('selectProduct', $product->id)
            ->assertRedirect(route('sourcing.quote', [
                'sourcing' => $sourcing->slug,
                'sourceProduct' => $product->slug,
            ]));

        $this->assertSame(1, $product->fresh()->interest_count);
    }

    #[Test]
    public function submitting_the_quote_form_creates_a_pending_quote_with_attachments(): void
    {
        [$sourcing, $product] = $this->sourcingWithProduct();
        $admin = $this->admin();
        $buyer = User::factory()->create();
        $this->actingAs($buyer);

        Livewire::test(SourcingQuotePage::class, ['sourcing' => $sourcing->slug, 'sourceProduct' => $product->slug])
            ->set('moq', 150)
            ->set('message', 'Logo en rouge et blanc, livraison avant fin du mois.')
            ->set('attachments', [UploadedFile::fake()->image('logo.png')])
            ->call('submit')
            ->assertRedirect(route('sourcing.show', $sourcing->slug));

        $quote = Quote::first();

        $this->assertNotNull($quote);
        $this->assertSame($buyer->id, $quote->client_id);
        $this->assertSame('sourcing', $quote->type);
        $this->assertSame($product->id, $quote->source_product_id);
        $this->assertSame(150, $quote->moq);
        $this->assertSame('pending', $quote->status);
        $this->assertCount(1, $quote->attachments);

        // submit() also opens the thread the admin actually sees — the quote
        // row alone isn't visible to them as a conversation, only as a record.
        $opening = Message::where('quote_id', $quote->id)->first();

        $this->assertNotNull($opening);
        $this->assertSame($buyer->id, $opening->sender_id);
        $this->assertSame($admin->id, $opening->receiver_id);
        $this->assertStringContainsString('Logo en rouge et blanc', $opening->content);
        $this->assertCount(1, $opening->attachments);
    }

    #[Test]
    public function the_quote_form_requires_a_message(): void
    {
        [$sourcing, $product] = $this->sourcingWithProduct();
        $this->actingAs(User::factory()->create());

        Livewire::test(SourcingQuotePage::class, ['sourcing' => $sourcing->slug, 'sourceProduct' => $product->slug])
            ->set('message', '')
            ->call('submit')
            ->assertHasErrors(['message' => 'required']);

        $this->assertSame(0, Quote::count());
    }

    #[Test]
    public function a_guest_cannot_reach_the_quote_form_directly(): void
    {
        [$sourcing, $product] = $this->sourcingWithProduct();

        $this->get(route('sourcing.quote', ['sourcing' => $sourcing->slug, 'sourceProduct' => $product->slug]))
            ->assertRedirect(route('login'));
    }
}
