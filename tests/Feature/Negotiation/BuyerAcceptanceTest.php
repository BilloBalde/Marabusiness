<?php

namespace Tests\Feature\Negotiation;

use App\Livewire\RfqChat;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Services\OrderNegotiation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The buyer's side, driven through the screen they actually use.
 *
 * RfqChat already authorises only the buyer or the owning vendor, and already
 * redirects to the orders list on acceptance because that is where the "Payer"
 * button lives. The negotiation reuses all of it.
 */
class BuyerAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(int $stock = 10): array
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendorUser = User::factory()->create();
        $vendor = Vendor::factory()->create([
            'user_id' => $vendorUser->id,
            'currency_id' => $currency->id,
            'is_active' => true,
        ]);
        $buyer = User::factory()->create();

        $product = Product::create([
            'name' => 'Article',
            'slug' => 'article-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
        ]);

        $listing = VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 100000,
            'stock' => $stock,
            'is_active' => true,
        ]);

        $order = (new OrderNegotiation())->open(
            buyer: $buyer,
            vendor: $vendor,
            items: [[
                'product_id' => $product->id,
                'vendor_id' => $vendor->id,
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

        return compact('vendor', 'buyer', 'order', 'listing');
    }

    private function priced(array $s, float $total = 180000, int $days = 3): Order
    {
        return (new OrderNegotiation())->price($s['order']->fresh(), $s['vendor'], $total, $days);
    }

    #[Test]
    public function the_buyer_accepts_the_price_and_lands_where_they_can_pay(): void
    {
        $s = $this->scenario();
        $this->priced($s);
        $this->actingAs($s['buyer']);

        Livewire::test(RfqChat::class, ['rfq' => $s['order']->negotiation])
            ->call('acceptNegotiatedPrice')
            ->assertRedirect(route('my-orders'));

        $order = $s['order']->fresh();

        $this->assertSame('new', $order->status);
        $this->assertEqualsWithDelta(180000, (float) $order->grand_total, 0.01);
        $this->assertFalse($order->isNegotiating());
    }

    #[Test]
    public function refusing_keeps_the_order_open_for_another_round(): void
    {
        $s = $this->scenario();
        $this->priced($s);
        $this->actingAs($s['buyer']);

        Livewire::test(RfqChat::class, ['rfq' => $s['order']->negotiation])
            ->set('rejectionReason', 'Encore trop cher')
            ->call('refuseNegotiatedPrice');

        $order = $s['order']->fresh();

        $this->assertTrue($order->isNegotiating());
        $this->assertNull($order->negotiated_total);
        $this->assertSame(Order::NEGOTIATION_OPEN, $order->negotiation_status);
    }

    #[Test]
    public function the_buyer_can_walk_away(): void
    {
        $s = $this->scenario();
        $this->actingAs($s['buyer']);

        Livewire::test(RfqChat::class, ['rfq' => $s['order']->negotiation])
            ->call('cancelNegotiation')
            ->assertRedirect(route('my-orders'));

        $this->assertSame('cancelled', $s['order']->fresh()->status);
    }

    #[Test]
    public function the_vendor_cannot_accept_on_the_buyers_behalf(): void
    {
        $s = $this->scenario();
        $this->priced($s);
        // The vendor is authorised to read this thread, but not to take the price.
        $this->actingAs($s['vendor']->user);

        Livewire::test(RfqChat::class, ['rfq' => $s['order']->negotiation])
            ->call('acceptNegotiatedPrice')
            ->assertForbidden();

        $this->assertTrue($s['order']->fresh()->isNegotiating());
    }

    #[Test]
    public function a_price_agreed_on_goods_that_sold_out_is_reported_not_swallowed(): void
    {
        // Stock is deliberately not held during the discussion, so this can
        // happen. The buyer has to be told rather than left on a spinner.
        $s = $this->scenario();
        $this->priced($s);
        $s['listing']->update(['stock' => 0]);
        $this->actingAs($s['buyer']);

        Livewire::test(RfqChat::class, ['rfq' => $s['order']->negotiation])
            ->call('acceptNegotiatedPrice')
            // No redirect to the orders list: the acceptance did not go through.
            ->assertNoRedirect();

        // The order is untouched and still negotiable — the buyer can ask the
        // vendor to price what is actually left. The wording of the refusal is
        // pinned in OrderNegotiationTest, against the service that produces it.
        $this->assertSame(Order::STATUS_NEGOTIATING, $s['order']->fresh()->status);
        $this->assertEqualsWithDelta(200000, (float) $s['order']->fresh()->items()->sum('total_amount'), 0.01);
    }
}
