<?php

namespace Tests\Feature\Chat;

use App\Livewire\CustomerChat;
use App\Models\Currency;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The /chats/{chatWithId} route only ever had one link pointing at it — the
 * navbar's "Mes chats" — and that link hardcoded the platform's manager id.
 * A vendor could send a customer as many messages as they liked; the
 * customer had no way to land on that conversation at all.
 */
class CustomerChatTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        // loadContacts() also looks up the manager role directly (to offer it
        // as a contact) — Spatie's role() scope throws if the role row itself
        // doesn't exist, not just if nobody holds it.
        Role::findOrCreate('customer', 'web');
        Role::findOrCreate('manager', 'web');
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

    private function vendorWithOrder(User $customer): array
    {
        Role::findOrCreate('vendor', 'web');
        $vendorUser = User::factory()->create();
        $vendorUser->assignRole('vendor');

        $vendor = Vendor::factory()->create([
            'user_id' => $vendorUser->id,
            'currency_id' => Currency::factory()->create()->id,
        ]);

        Order::create([
            'order_number' => 'TEST-' . uniqid(),
            'user_id' => $customer->id,
            'vendor_id' => $vendor->id,
            'grand_total' => 10,
            'total_remaining' => 10,
            'total_paid' => 0,
            'status' => 'new',
            'payment_status' => 'paid',
            'payment_method' => 'test',
        ]);

        return [$vendorUser, $vendor];
    }

    #[Test]
    public function the_route_no_longer_requires_a_forced_contact_id(): void
    {
        $customer = $this->customer();
        [$vendorUser] = $this->vendorWithOrder($customer);

        $this->actingAs($customer)
            ->get(route('chats'))
            ->assertSuccessful();
    }

    #[Test]
    public function landing_with_no_contact_auto_selects_the_vendor_who_has_unread_messages(): void
    {
        $customer = $this->customer();
        // "Aaron" sorts before the vendor alphabetically — proving the
        // auto-pick is driven by unread messages, not by name order.
        [$vendorUser] = $this->vendorWithOrder($customer);
        $vendorUser->forceFill(['name' => 'Zzz Vendor'])->save();

        Message::create([
            'sender_id' => $vendorUser->id,
            'receiver_id' => $customer->id,
            'content' => 'Votre commande est prête.',
            'is_read' => false,
        ]);

        Livewire::actingAs($customer)
            ->test(CustomerChat::class)
            ->assertSet('chatWithId', $vendorUser->id)
            ->assertSee('Votre commande est prête.');
    }

    #[Test]
    public function a_customer_sees_messages_from_a_vendor_they_ordered_from(): void
    {
        $customer = $this->customer();
        [$vendorUser] = $this->vendorWithOrder($customer);

        Message::create([
            'sender_id' => $vendorUser->id,
            'receiver_id' => $customer->id,
            'content' => 'Bonjour, votre colis part demain.',
            'is_read' => false,
        ]);

        Livewire::actingAs($customer)
            ->test(CustomerChat::class, ['chatWithId' => $vendorUser->id])
            ->assertSee('Bonjour, votre colis part demain.');
    }

    #[Test]
    public function selecting_a_contact_marks_their_messages_read(): void
    {
        $customer = $this->customer();
        [$vendorUser] = $this->vendorWithOrder($customer);

        $message = Message::create([
            'sender_id' => $vendorUser->id,
            'receiver_id' => $customer->id,
            'content' => 'Un message non lu.',
            'is_read' => false,
        ]);

        Livewire::actingAs($customer)
            ->test(CustomerChat::class)
            ->call('selectContact', $vendorUser->id);

        $this->assertTrue((bool) $message->fresh()->is_read);
    }

    #[Test]
    public function a_customer_can_reply_to_the_vendor(): void
    {
        $customer = $this->customer();
        [$vendorUser] = $this->vendorWithOrder($customer);

        Livewire::actingAs($customer)
            ->test(CustomerChat::class, ['chatWithId' => $vendorUser->id])
            ->set('message', 'Merci, à bientôt.')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'sender_id' => $customer->id,
            'receiver_id' => $vendorUser->id,
            'content' => 'Merci, à bientôt.',
        ]);
    }
}
