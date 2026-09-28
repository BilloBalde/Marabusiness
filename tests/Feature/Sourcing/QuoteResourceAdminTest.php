<?php

namespace Tests\Feature\Sourcing;

use App\Filament\Resources\QuoteResource\Pages\ListQuotes;
use App\Filament\Resources\QuoteResource\Pages\ViewQuote;
use App\Models\Message;
use App\Models\Quote;
use App\Models\SourceProduct;
use App\Models\Sourcing;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuoteResourceAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A genuine WebP image, not just a file named ".webp" — image exports from
     * ChatGPT and some phone browsers are commonly WebP even when downloaded
     * with a ".png" name, and the mimes rule sniffs real content, not the name.
     */
    private function fakeWebp(): UploadedFile
    {
        return UploadedFile::fake()->image('photo.webp', 2, 2);
    }

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

    private function quoteWithOpeningMessage(User $client, User $admin): Quote
    {
        $sourcing = Sourcing::create([
            'slug' => 'textile',
            'title' => ['fr' => 'Textile'],
            'short_description' => ['fr' => 'Vêtements et tissus'],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $sourceProduct = SourceProduct::create([
            'sourcing_id' => $sourcing->id,
            'slug' => 'polo-brode',
            'name' => ['fr' => 'Polo brodé'],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $quote = Quote::create([
            'client_id' => $client->id,
            'type' => 'sourcing',
            'source_product_id' => $sourceProduct->id,
            'moq' => 100,
            'message' => 'Bonjour, je souhaite un devis pour 100 polos.',
            'status' => 'pending',
        ]);

        $quote->postMessage($client, $quote->message);

        return $quote;
    }

    #[Test]
    public function the_list_page_renders(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create();
        $this->quoteWithOpeningMessage($client, $admin);

        $this->actingAsAdminPanel($admin);

        Livewire::test(ListQuotes::class)->assertSuccessful();
    }

    #[Test]
    public function the_view_page_shows_the_opening_message(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create(['name' => 'Fatou Diop']);
        $quote = $this->quoteWithOpeningMessage($client, $admin);

        $this->actingAsAdminPanel($admin);

        Livewire::test(ViewQuote::class, ['record' => $quote->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('Fatou Diop')
            ->assertSee('Bonjour, je souhaite un devis pour 100 polos.');
    }

    #[Test]
    public function the_admin_can_reply_and_the_message_is_attributed_correctly(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create();
        $quote = $this->quoteWithOpeningMessage($client, $admin);

        $this->actingAsAdminPanel($admin);

        Livewire::test(ViewQuote::class, ['record' => $quote->getRouteKey()])
            ->set('reply', 'Merci, nous revenons vers vous avec un prix demain.')
            ->call('sendReply')
            ->assertHasNoErrors();

        $reply = Message::where('quote_id', $quote->id)
            ->where('content', 'Merci, nous revenons vers vous avec un prix demain.')
            ->first();

        $this->assertNotNull($reply);
        $this->assertSame($admin->id, $reply->sender_id);
        $this->assertSame($client->id, $reply->receiver_id);
    }

    #[Test]
    public function a_webp_attachment_is_accepted_on_reply(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create();
        $quote = $this->quoteWithOpeningMessage($client, $admin);

        $this->actingAsAdminPanel($admin);

        Livewire::test(ViewQuote::class, ['record' => $quote->getRouteKey()])
            ->set('reply', 'Voici le catalogue en image.')
            ->set('attachments', [$this->fakeWebp()])
            ->call('sendReply')
            ->assertHasNoErrors();

        $reply = Message::where('quote_id', $quote->id)
            ->where('content', 'Voici le catalogue en image.')
            ->first();

        $this->assertCount(1, $reply->attachments);
    }

    #[Test]
    public function replying_with_an_empty_message_is_rejected(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create();
        $quote = $this->quoteWithOpeningMessage($client, $admin);

        $this->actingAsAdminPanel($admin);

        Livewire::test(ViewQuote::class, ['record' => $quote->getRouteKey()])
            ->set('reply', '')
            ->call('sendReply')
            ->assertHasErrors(['reply' => 'required']);
    }

    #[Test]
    public function status_actions_change_the_quote_status(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create();
        $quote = $this->quoteWithOpeningMessage($client, $admin);

        $this->actingAsAdminPanel($admin);

        Livewire::test(ViewQuote::class, ['record' => $quote->getRouteKey()])
            ->callAction('markProcessing');

        $this->assertSame('processing', $quote->refresh()->status);

        Livewire::test(ViewQuote::class, ['record' => $quote->getRouteKey()])
            ->callAction('markValidated');

        $this->assertSame('validated', $quote->refresh()->status);
    }

    #[Test]
    public function the_cancel_action_marks_the_quote_cancelled(): void
    {
        $admin = $this->admin();
        $client = User::factory()->create();
        $quote = $this->quoteWithOpeningMessage($client, $admin);

        $this->actingAsAdminPanel($admin);

        Livewire::test(ViewQuote::class, ['record' => $quote->getRouteKey()])
            ->callAction('markCancelled');

        $this->assertSame('cancelled', $quote->refresh()->status);
    }
}
