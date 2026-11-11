<?php

namespace Tests\Feature\Sourcing;

use App\Livewire\MyQuotesPage;
use App\Livewire\QuoteDetailPage;
use App\Models\Message;
use App\Models\Quote;
use App\Models\SourceProduct;
use App\Models\Sourcing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuoteClientPagesTest extends TestCase
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

    private function quoteFor(User $client, User $admin, ?string $productName = 'Casquette brodée'): Quote
    {
        $sourcing = Sourcing::firstOrCreate(
            ['slug' => 'textile'],
            ['title' => ['fr' => 'Textile'], 'short_description' => ['fr' => '...'], 'is_active' => true],
        );

        $product = SourceProduct::create([
            'sourcing_id' => $sourcing->id,
            'slug' => str()->slug($productName) . '-' . uniqid(),
            'name' => ['fr' => $productName],
            'is_active' => true,
        ]);

        $quote = Quote::create([
            'client_id' => $client->id,
            'type' => 'sourcing',
            'source_product_id' => $product->id,
            'moq' => 100,
            'message' => 'Bonjour, un devis pour 100 unités.',
            'status' => 'pending',
        ]);

        $quote->postMessage($client, $quote->message);

        return $quote;
    }

    #[Test]
    public function my_quotes_page_lists_only_the_authenticated_clients_quotes(): void
    {
        $admin = $this->admin();
        $me = User::factory()->create();
        $someoneElse = User::factory()->create();

        $mine = $this->quoteFor($me, $admin, 'Polo brodé');
        $this->quoteFor($someoneElse, $admin, 'Sac imprimé');

        $this->actingAs($me);

        Livewire::test(MyQuotesPage::class)
            ->assertSee('Polo brodé')
            ->assertDontSee('Sac imprimé');
    }

    #[Test]
    public function quote_detail_page_shows_the_thread_and_lets_the_client_reply(): void
    {
        $admin = $this->admin();
        $me = User::factory()->create();
        $quote = $this->quoteFor($me, $admin);

        // A reply from the admin should show up in the client's thread too.
        $quote->postMessage($admin, 'Merci, nous vous envoyons un prix sous 48h.');

        $this->actingAs($me);

        Livewire::test(QuoteDetailPage::class, ['quote' => $quote->id])
            ->assertSee('Bonjour, un devis pour 100 unités.')
            ->assertSee('Merci, nous vous envoyons un prix sous 48h.')
            ->set('reply', 'Parfait, merci !')
            ->call('sendReply')
            ->assertHasNoErrors();

        $lastMessage = Message::where('quote_id', $quote->id)->latest('id')->first();

        $this->assertSame('Parfait, merci !', $lastMessage->content);
        $this->assertSame($me->id, $lastMessage->sender_id);
        $this->assertSame($admin->id, $lastMessage->receiver_id);
    }

    #[Test]
    public function a_webp_attachment_is_accepted_on_reply(): void
    {
        $admin = $this->admin();
        $me = User::factory()->create();
        $quote = $this->quoteFor($me, $admin);

        $this->actingAs($me);

        Livewire::test(QuoteDetailPage::class, ['quote' => $quote->id])
            ->set('reply', 'Voici une image du produit.')
            ->set('attachments', [$this->fakeWebp()])
            ->call('sendReply')
            ->assertHasNoErrors();

        $lastMessage = Message::where('quote_id', $quote->id)->latest('id')->first();

        $this->assertCount(1, $lastMessage->attachments);
    }

    #[Test]
    public function replying_with_an_empty_message_is_rejected(): void
    {
        $admin = $this->admin();
        $me = User::factory()->create();
        $quote = $this->quoteFor($me, $admin);

        $this->actingAs($me);

        Livewire::test(QuoteDetailPage::class, ['quote' => $quote->id])
            ->set('reply', '')
            ->call('sendReply')
            ->assertHasErrors(['reply' => 'required']);
    }

    #[Test]
    public function a_client_cannot_view_another_clients_quote(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $quote = $this->quoteFor($owner, $admin);

        $this->actingAs($intruder);

        $this->get(route('quotes.show', $quote->id))->assertNotFound();
    }

    #[Test]
    public function a_guest_cannot_reach_my_quotes_or_a_quote_detail(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $quote = $this->quoteFor($owner, $admin);

        $this->get(route('quotes.mine'))->assertRedirect(route('login'));
        $this->get(route('quotes.show', $quote->id))->assertRedirect(route('login'));
    }
}
