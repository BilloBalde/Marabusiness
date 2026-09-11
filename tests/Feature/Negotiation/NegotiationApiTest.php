<?php

namespace Tests\Feature\Negotiation;

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Services\OrderNegotiation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La négociation depuis l'application mobile.
 *
 * Le contrôleur ne porte aucune règle : il appelle OrderNegotiation, le même
 * service que le checkout web. Ces tests vérifient donc surtout la traduction —
 * qu'un refus du service devienne un 409 et non un 500, qu'un client ne voie que
 * ses propres négociations, et que les frais de port soient calculés côté serveur
 * et non acceptés depuis le téléphone.
 */
class NegotiationApiTest extends TestCase
{
    use RefreshDatabase;

    private function listing(Vendor $vendor, string $name = 'Article'): VendorProduct
    {
        $product = Product::create([
            'name' => $name,
            'slug' => \Str::slug($name) . '-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
        ]);

        return VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 100000,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    private function basket(): array
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $shopA = Vendor::factory()->create(['currency_id' => $currency->id, 'is_active' => true]);
        $shopB = Vendor::factory()->create(['currency_id' => $currency->id, 'is_active' => true]);

        $a = $this->listing($shopA, 'Article A');
        $b = $this->listing($shopB, 'Article B');

        $buyer = User::factory()->create();

        foreach ([$a, $b] as $listing) {
            CartItem::create([
                'user_id' => $buyer->id,
                'vendor_product_id' => $listing->id,
                'quantity' => 2,
                'cart_key' => 'key-' . $listing->id,
            ]);
        }

        return compact('buyer', 'shopA', 'shopB', 'a', 'b');
    }

    private function payload(array $s, array $overrides = []): array
    {
        return array_merge([
            'vendor_id' => $s['shopA']->id,
            'selected_ids' => [$s['a']->id, $s['b']->id],
            'address' => [
                'first_name' => 'A', 'last_name' => 'B', 'phone' => '600000000',
                'street_address' => 'Rue 1', 'city' => 'Conakry', 'state' => 'Conakry',
                'country' => 'Guinea',
            ],
            'shipping_carrier' => 'local',
            'target_price' => 150000,
            'message' => 'Un geste possible ?',
        ], $overrides);
    }

    /** A negotiation already open, built through the service rather than the API. */
    private function openNegotiation(): array
    {
        $s = $this->basket();

        $order = (new OrderNegotiation())->open(
            buyer: $s['buyer'],
            vendor: $s['shopA'],
            items: [[
                'product_id' => $s['a']->product_id,
                'vendor_id' => $s['shopA']->id,
                'quantity' => 2,
                'unit_amount' => 100000,
                'total_amount' => 200000,
            ]],
            shippingLocal: 20000,
            shippingUsd: 2.4,
            targetPrice: 150000,
            message: 'Un geste ?',
            address: [
                'first_name' => 'A', 'last_name' => 'B', 'phone' => '600000000',
                'street_address' => 'Rue 1', 'city' => 'Conakry', 'state' => 'Conakry',
                'country' => 'Guinea',
            ],
        );

        return $s + ['order' => $order];
    }

    #[Test]
    public function opening_a_negotiation_creates_an_unpayable_order(): void
    {
        $s = $this->basket();
        Sanctum::actingAs($s['buyer']);

        $response = $this->postJson('/api/v1/negotiations', $this->payload($s));

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('negotiation.negotiation_status', Order::NEGOTIATION_OPEN)
            ->assertJsonPath('negotiation.status', Order::STATUS_NEGOTIATING)
            ->assertJsonPath('negotiation.has_live_offer', false);

        $order = Order::where('vendor_id', $s['shopA']->id)->first();

        $this->assertNotNull($order);
        $this->assertNotNull($order->negotiation);
        $this->assertSame('pending', $order->payment_status);
    }

    #[Test]
    public function only_that_vendors_lines_leave_the_basket(): void
    {
        // The other shop's items stay checkout-able; that is the whole point of
        // negotiating per vendor.
        $s = $this->basket();
        Sanctum::actingAs($s['buyer']);

        $this->postJson('/api/v1/negotiations', $this->payload($s))->assertCreated();

        $remaining = CartItem::where('user_id', $s['buyer']->id)->pluck('vendor_product_id');

        $this->assertFalse($remaining->contains($s['a']->id));
        $this->assertTrue($remaining->contains($s['b']->id));
    }

    #[Test]
    public function a_shipping_cost_sent_by_the_phone_is_ignored(): void
    {
        // The client cannot name the delivery charge that ends up on the invoice.
        $s = $this->basket();
        Sanctum::actingAs($s['buyer']);

        $this->postJson('/api/v1/negotiations', $this->payload($s, [
            'shipping_amount' => 999999,
            'shipping_amount_usd' => 999999,
        ]))->assertCreated();

        $order = Order::where('vendor_id', $s['shopA']->id)->first();

        $this->assertNotEquals(999999, (float) $order->shipping_amount);
    }

    #[Test]
    public function a_basket_without_that_vendor_is_refused(): void
    {
        $s = $this->basket();
        Sanctum::actingAs($s['buyer']);

        $this->postJson('/api/v1/negotiations', $this->payload($s, [
            'selected_ids' => [$s['b']->id],
        ]))->assertStatus(422);
    }

    #[Test]
    public function a_guest_cannot_open_a_negotiation(): void
    {
        $s = $this->basket();

        $this->postJson('/api/v1/negotiations', $this->payload($s))->assertUnauthorized();
    }

    #[Test]
    public function the_buyer_lists_their_own_negotiations_only(): void
    {
        $s = $this->openNegotiation();
        $stranger = User::factory()->create();

        Sanctum::actingAs($s['buyer']);
        $this->getJson('/api/v1/negotiations')
            ->assertOk()
            ->assertJsonPath('negotiations.0.order_number', $s['order']->order_number);

        Sanctum::actingAs($stranger);
        $this->getJson('/api/v1/negotiations')
            ->assertOk()
            ->assertJsonPath('negotiations', []);
    }

    #[Test]
    public function the_detail_carries_the_thread(): void
    {
        $s = $this->openNegotiation();
        Sanctum::actingAs($s['buyer']);

        $response = $this->getJson("/api/v1/negotiations/{$s['order']->id}");

        $response->assertOk()
            ->assertJsonPath('negotiation.order_number', $s['order']->order_number)
            ->assertJsonPath('messages.0.from_buyer', true);

        $this->assertStringContainsString('Un geste', $response->json('messages.0.message'));
    }

    #[Test]
    public function another_buyers_negotiation_is_not_found(): void
    {
        $s = $this->openNegotiation();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/negotiations/{$s['order']->id}")->assertNotFound();
    }

    #[Test]
    public function the_buyer_can_write_in_the_thread(): void
    {
        $s = $this->openNegotiation();
        Sanctum::actingAs($s['buyer']);

        $this->postJson("/api/v1/negotiations/{$s['order']->id}/messages", [
            'message' => 'Et à 170 000 ?',
        ])->assertCreated();

        $this->assertStringContainsString(
            'Et à 170 000 ?',
            $s['order']->negotiation->messages()->pluck('message')->implode("\n")
        );
    }

    #[Test]
    public function accepting_applies_the_price_and_makes_the_order_payable(): void
    {
        $s = $this->openNegotiation();
        (new OrderNegotiation())->price($s['order']->fresh(), $s['shopA'], 180000, 3);

        Sanctum::actingAs($s['buyer']);

        $this->postJson("/api/v1/negotiations/{$s['order']->id}/accept")
            ->assertOk()
            ->assertJsonPath('negotiation.negotiation_status', Order::NEGOTIATION_AGREED);

        $order = $s['order']->fresh();

        $this->assertSame('new', $order->status);
        $this->assertEqualsWithDelta(180000, (float) $order->grand_total, 0.01);
    }

    #[Test]
    public function accepting_an_expired_price_answers_409_rather_than_500(): void
    {
        $s = $this->openNegotiation();
        (new OrderNegotiation())->price($s['order']->fresh(), $s['shopA'], 180000, 3);
        $s['order']->fresh()->update(['negotiated_expires_at' => now()->subMinute()]);

        Sanctum::actingAs($s['buyer']);

        $this->postJson("/api/v1/negotiations/{$s['order']->id}/accept")
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertTrue($s['order']->fresh()->isNegotiating());
    }

    #[Test]
    public function refusing_reopens_the_discussion(): void
    {
        $s = $this->openNegotiation();
        (new OrderNegotiation())->price($s['order']->fresh(), $s['shopA'], 180000, 3);

        Sanctum::actingAs($s['buyer']);

        $this->postJson("/api/v1/negotiations/{$s['order']->id}/refuse", ['reason' => 'Trop cher'])
            ->assertOk()
            ->assertJsonPath('negotiation.negotiation_status', Order::NEGOTIATION_OPEN);

        $this->assertTrue($s['order']->fresh()->isNegotiating());
    }

    #[Test]
    public function cancelling_closes_the_order(): void
    {
        $s = $this->openNegotiation();
        Sanctum::actingAs($s['buyer']);

        $this->postJson("/api/v1/negotiations/{$s['order']->id}/cancel", ['reason' => 'Plus besoin'])
            ->assertOk();

        $order = $s['order']->fresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertNotNull($order->cancelled_at);
    }

    #[Test]
    public function an_order_under_negotiation_refuses_payment(): void
    {
        // The guard that matters most: nothing may be collected on a price nobody
        // has agreed to.
        $s = $this->openNegotiation();
        Sanctum::actingAs($s['buyer']);

        $this->postJson("/api/v1/orders/{$s['order']->id}/payment/session", [
            'payment_method' => 'lengopay',
        ])->assertStatus(409);
    }

    #[Test]
    public function writing_in_a_finished_negotiation_is_refused(): void
    {
        $s = $this->openNegotiation();
        (new OrderNegotiation())->cancel($s['order'], $s['buyer']);

        Sanctum::actingAs($s['buyer']);

        $this->postJson("/api/v1/negotiations/{$s['order']->id}/messages", ['message' => 'Allo ?'])
            ->assertStatus(409);
    }
}
