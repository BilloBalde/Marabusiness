<?php

namespace Tests\Feature\Chat;

use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * ChatController has served five routes since it was written and nothing ever
 * called them — ApiEndpoints declared three, the app used none, and there were
 * no tests. The mobile client now depends on these exact shapes, so they are
 * pinned here.
 *
 * Who may talk to whom is decided entirely server-side by canChat(): a buyer may
 * message a vendor only where an order between them exists. The client cannot
 * loosen that, and these tests prove it does not need to.
 */
class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['customer', 'vendor', 'manager'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    private function customer(): User
    {
        return User::factory()->create()->assignRole('customer');
    }

    /** @return array{0: User, 1: Vendor} */
    private function vendorUser(): array
    {
        $user = User::factory()->create()->assignRole('vendor');
        $vendor = Vendor::factory()->create(['user_id' => $user->id, 'is_active' => true]);

        return [$user, $vendor];
    }

    #[Test]
    public function a_buyer_sees_the_vendors_they_have_ordered_from(): void
    {
        [$vendorUser, $vendor] = $this->vendorUser();
        $buyer = $this->customer();

        Order::factory()->create(['user_id' => $buyer->id, 'vendor_id' => $vendor->id]);

        Sanctum::actingAs($buyer);

        $this->getJson('/api/v1/chats')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['id' => $vendorUser->id])
            // The contact card the mobile list renders.
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'avatar', 'role', 'last_message', 'unread_count']],
            ]);
    }

    #[Test]
    public function a_buyer_does_not_see_a_vendor_they_never_ordered_from(): void
    {
        $this->vendorUser();
        Sanctum::actingAs($this->customer());

        $this->getJson('/api/v1/chats')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function a_buyer_can_message_a_vendor_they_ordered_from(): void
    {
        [$vendorUser, $vendor] = $this->vendorUser();
        $buyer = $this->customer();
        Order::factory()->create(['user_id' => $buyer->id, 'vendor_id' => $vendor->id]);

        Sanctum::actingAs($buyer);

        $this->postJson('/api/v1/chats/send', [
            'receiver_id' => $vendorUser->id,
            'message' => 'Ma commande part quand ?',
        ])
            ->assertOk()
            ->assertJsonPath('data.content', 'Ma commande part quand ?')
            // The client lays the conversation out from this flag alone, so it
            // has to be present and true for the sender.
            ->assertJsonPath('data.is_from_me', true);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $buyer->id,
            'receiver_id' => $vendorUser->id,
            'is_read' => false,
        ]);
    }

    #[Test]
    public function messaging_a_vendor_with_no_order_between_you_is_refused(): void
    {
        [$vendorUser] = $this->vendorUser();
        Sanctum::actingAs($this->customer());

        $this->postJson('/api/v1/chats/send', [
            'receiver_id' => $vendorUser->id,
            'message' => 'Bonjour',
        ])->assertStatus(403);

        $this->assertDatabaseCount('messages', 0);
    }

    #[Test]
    public function reading_a_conversation_marks_the_other_sides_messages_read(): void
    {
        // The client relies on this: it never calls mark-read, because opening
        // the conversation is what clears the badge.
        [$vendorUser, $vendor] = $this->vendorUser();
        $buyer = $this->customer();
        Order::factory()->create(['user_id' => $buyer->id, 'vendor_id' => $vendor->id]);

        Message::create([
            'sender_id' => $vendorUser->id,
            'receiver_id' => $buyer->id,
            'content' => 'Elle part demain',
            'is_read' => false,
        ]);

        Sanctum::actingAs($buyer);

        $this->getJson("/api/v1/chats/{$vendorUser->id}")
            ->assertOk()
            ->assertJsonPath('data.messages.0.content', 'Elle part demain')
            ->assertJsonPath('data.messages.0.is_from_me', false);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $vendorUser->id,
            'receiver_id' => $buyer->id,
            'is_read' => true,
        ]);
    }

    #[Test]
    public function reading_someone_elses_conversation_is_refused(): void
    {
        [$vendorUser] = $this->vendorUser();
        Sanctum::actingAs($this->customer());

        $this->getJson("/api/v1/chats/{$vendorUser->id}")->assertStatus(403);
    }

    #[Test]
    public function the_unread_count_drives_the_profile_badge(): void
    {
        [$vendorUser, $vendor] = $this->vendorUser();
        $buyer = $this->customer();
        Order::factory()->create(['user_id' => $buyer->id, 'vendor_id' => $vendor->id]);

        foreach (['un', 'deux'] as $body) {
            Message::create([
                'sender_id' => $vendorUser->id,
                'receiver_id' => $buyer->id,
                'content' => $body,
                'is_read' => false,
            ]);
        }

        Sanctum::actingAs($buyer);

        $this->getJson('/api/v1/chats/unread/count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 2);
    }

    #[Test]
    public function chat_requires_authentication(): void
    {
        $this->getJson('/api/v1/chats')->assertStatus(401);
    }
}
