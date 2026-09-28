<?php

namespace Tests\Feature\Sourcing;

use App\Filament\Resources\QuoteResource\Pages\ViewQuote;
use App\Livewire\SourcingQuotePage;
use App\Mail\NewSourcingQuoteReceived;
use App\Mail\QuoteStatusUpdated;
use App\Models\Quote;
use App\Models\SourceProduct;
use App\Models\Sourcing;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuoteNotificationsTest extends TestCase
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
            'short_description' => ['fr' => '...'],
            'is_active' => true,
        ]);

        $product = SourceProduct::create([
            'sourcing_id' => $sourcing->id,
            'slug' => 'casquette-brodee',
            'name' => ['fr' => 'Casquette brodée'],
            'is_active' => true,
        ]);

        return [$sourcing, $product];
    }

    #[Test]
    public function the_admin_is_emailed_when_a_client_submits_a_quote(): void
    {
        Mail::fake();
        $admin = $this->admin();
        [$sourcing, $product] = $this->sourcingWithProduct();
        $buyer = User::factory()->create();
        $this->actingAs($buyer);

        Livewire::test(SourcingQuotePage::class, ['sourcing' => $sourcing->slug, 'sourceProduct' => $product->slug])
            ->set('message', 'Logo en rouge et blanc, livraison avant fin du mois.')
            ->call('submit');

        Mail::assertSent(
            NewSourcingQuoteReceived::class,
            fn (NewSourcingQuoteReceived $mail) => $mail->hasTo($admin->email)
        );
    }

    #[Test]
    public function the_new_quote_notification_template_renders(): void
    {
        // Mail::fake() never renders the view, so a broken template would only
        // surface in production.
        $admin = $this->admin();
        [, $product] = $this->sourcingWithProduct();
        $buyer = User::factory()->create(['name' => 'Fatou Diop']);

        $quote = Quote::create([
            'client_id' => $buyer->id,
            'type' => 'sourcing',
            'source_product_id' => $product->id,
            'moq' => 150,
            'message' => 'Logo en rouge et blanc.',
            'status' => 'pending',
        ]);

        $html = (new NewSourcingQuoteReceived($quote->fresh(['client', 'sourceProduct.sourcing'])))->render();

        $this->assertStringContainsString('Fatou Diop', $html);
        $this->assertStringContainsString('Casquette brodée', $html);
        $this->assertStringContainsString('Logo en rouge et blanc.', $html);
    }

    #[Test]
    public function the_client_is_emailed_when_the_admin_changes_the_quote_status(): void
    {
        Mail::fake();
        $admin = $this->admin();
        [, $product] = $this->sourcingWithProduct();
        $client = User::factory()->create();

        $quote = Quote::create([
            'client_id' => $client->id,
            'type' => 'sourcing',
            'source_product_id' => $product->id,
            'message' => 'Bonjour',
            'status' => 'pending',
        ]);
        $quote->postMessage($client, $quote->message);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(ViewQuote::class, ['record' => $quote->getRouteKey()])
            ->callAction('markValidated');

        Mail::assertSent(
            QuoteStatusUpdated::class,
            fn (QuoteStatusUpdated $mail) => $mail->hasTo($client->email) && $mail->quote->status === 'validated'
        );
    }

    #[Test]
    public function the_status_updated_template_renders_for_each_status(): void
    {
        [, $product] = $this->sourcingWithProduct();
        $client = User::factory()->create();

        $quote = Quote::create([
            'client_id' => $client->id,
            'type' => 'sourcing',
            'source_product_id' => $product->id,
            'message' => 'Bonjour',
            'status' => 'processing',
        ]);

        $processing = (new QuoteStatusUpdated($quote->fresh(['client', 'sourceProduct'])))->render();
        $this->assertStringContainsString('en cours de traitement', $processing);

        $quote->update(['status' => 'validated']);
        $validated = (new QuoteStatusUpdated($quote->fresh(['client', 'sourceProduct'])))->render();
        $this->assertStringContainsString('validée', $validated);

        $quote->update(['status' => 'cancelled']);
        $cancelled = (new QuoteStatusUpdated($quote->fresh(['client', 'sourceProduct'])))->render();
        $this->assertStringContainsString('annulée', $cancelled);
    }
}
