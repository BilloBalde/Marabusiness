<?php

namespace Tests\Feature\Negotiation;

use App\Livewire\CheckoutPage;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The buyer's entry point: a "Discuter le prix" button on each vendor group in
 * checkout.
 *
 * The group is the unit because that is what a vendor can actually concede on,
 * and because order_items carries no vendor_product_id — a line cannot be traced
 * back to the listing it came from, so a per-line negotiation could not be
 * applied even if it were wanted.
 */
class CheckoutNegotiationTest extends TestCase
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

    /**
     * CheckoutPage::mount() reads the selection from the query string and
     * redirects to the cart when it is empty, so the ids have to be there before
     * the component mounts — setting selected_ids afterwards is too late.
     */
    private function checkout(array $vendorProductIds): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::withQueryParams(['selected' => implode(',', $vendorProductIds)])
            ->test(CheckoutPage::class);
    }

    private function fillAddress(\Livewire\Features\SupportTesting\Testable $page): \Livewire\Features\SupportTesting\Testable
    {
        return $page
            ->set('first_name', 'Fatou')
            ->set('last_name', 'Diallo')
            ->set('phone', '600000000')
            ->set('street_address', 'Rue 1')
            ->set('city', 'Conakry')
            ->set('state', 'Conakry')
            ->set('country', 'Guinea');
    }

    #[Test]
    public function a_buyer_opens_a_negotiation_on_one_shop_and_keeps_shopping_with_the_other(): void
    {
        $s = $this->basket();
        $this->actingAs($s['buyer']);

        $page = $this->checkout([$s['a']->id, $s['b']->id]);

        $this->fillAddress($page)
            ->call('openNegotiation', $s['shopA']->id)
            ->set('negotiationTargetPrice', 150000)
            ->set('negotiationMessage', 'Bonjour, un geste possible ?')
            ->call('startNegotiation');

        $order = Order::where('vendor_id', $s['shopA']->id)->first();

        $this->assertNotNull($order, 'the negotiation should have created an order');
        $this->assertSame(Order::STATUS_NEGOTIATING, $order->status);

        // Only shop A left the cart. Grouping by vendor exists precisely so the
        // rest of the basket stays checkout-able.
        $this->assertDatabaseMissing('cart_items', ['vendor_product_id' => $s['a']->id]);
        $this->assertDatabaseHas('cart_items', ['vendor_product_id' => $s['b']->id]);
    }

    #[Test]
    public function the_buyers_target_price_and_message_reach_the_vendor(): void
    {
        $s = $this->basket();
        $this->actingAs($s['buyer']);

        $page = $this->checkout([$s['a']->id]);

        $this->fillAddress($page)
            ->call('openNegotiation', $s['shopA']->id)
            ->set('negotiationTargetPrice', 150000)
            ->set('negotiationMessage', 'Bonjour, un geste possible ?')
            ->call('startNegotiation');

        $rfq = Order::where('vendor_id', $s['shopA']->id)->first()->negotiation;

        $this->assertEqualsWithDelta(150000, (float) $rfq->target_price, 0.01);
        $this->assertStringContainsString(
            'un geste possible',
            $rfq->messages()->pluck('message')->implode("\n")
        );
    }

    #[Test]
    public function a_negotiation_cannot_be_opened_without_a_message(): void
    {
        // The vendor is being asked a question; sending them an empty one wastes
        // a round trip for both sides.
        $s = $this->basket();
        $this->actingAs($s['buyer']);

        $page = $this->checkout([$s['a']->id]);

        $this->fillAddress($page)
            ->call('openNegotiation', $s['shopA']->id)
            ->set('negotiationMessage', '')
            ->call('startNegotiation')
            ->assertHasErrors('negotiationMessage');

        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function a_negotiation_cannot_be_opened_without_a_delivery_address(): void
    {
        // The order carries an address row and a shipping figure from the moment
        // it exists, exactly as a placed order does.
        $s = $this->basket();
        $this->actingAs($s['buyer']);

        $this->checkout([$s['a']->id])
            ->call('openNegotiation', $s['shopA']->id)
            ->set('negotiationMessage', 'Bonjour')
            ->call('startNegotiation')
            ->assertHasErrors('city');

        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function the_target_price_is_optional(): void
    {
        $s = $this->basket();
        $this->actingAs($s['buyer']);

        $page = $this->checkout([$s['a']->id]);

        $this->fillAddress($page)
            ->call('openNegotiation', $s['shopA']->id)
            ->set('negotiationMessage', 'Quel est votre meilleur prix ?')
            ->call('startNegotiation')
            ->assertHasNoErrors();

        $this->assertNull(Order::where('vendor_id', $s['shopA']->id)->first()->negotiation->target_price);
    }
}
