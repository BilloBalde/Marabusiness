<?php

namespace Tests\Feature\Negotiation;

use App\Livewire\UserRfqsPage;
use App\Models\Brand;
use App\Models\BulkRfq;
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
 * The buyer's list of negotiations, /my-rfqs.
 *
 * The table was built for the single-product quote flow and carries both kinds of
 * row now: a request about one product, and a negotiation about a whole vendor
 * basket. A basket negotiation has no product_id at all, so every column that
 * reached through `product` had to learn to show the order instead — hence the
 * tests below asserting on what each row actually names.
 */
class NegotiationListPageTest extends TestCase
{
    use RefreshDatabase;

    private function negotiation(): array
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendor = Vendor::factory()->create([
            'currency_id' => $currency->id,
            'is_active' => true,
            'store_name' => 'Boutique Kaloum',
        ]);
        $buyer = User::factory()->create();

        $product = Product::create([
            'name' => 'Article',
            'slug' => 'article-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
        ]);

        VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 100000,
            'stock' => 10,
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

        return compact('vendor', 'buyer', 'order', 'product');
    }

    #[Test]
    public function a_basket_negotiation_is_named_by_its_order(): void
    {
        // The row used to fall back to the literal word "Product" here, because a
        // basket negotiation carries no product_id.
        $s = $this->negotiation();

        Livewire::actingAs($s['buyer'])
            ->test(UserRfqsPage::class)
            ->assertSee('Commande ' . $s['order']->order_number)
            ->assertSee('Boutique Kaloum')
            ->assertSee('1 article(s) du panier');
    }

    #[Test]
    public function the_page_renders_in_french(): void
    {
        $this->negotiation();

        Livewire::actingAs(User::first())
            ->test(UserRfqsPage::class)
            ->assertSee('Mes négociations')
            ->assertSee('En attente du vendeur')
            ->assertDontSee('Price Target')
            ->assertDontSee('Submitted');
    }

    #[Test]
    public function a_price_the_vendor_proposed_shows_on_the_row(): void
    {
        $s = $this->negotiation();
        (new OrderNegotiation())->price($s['order']->fresh(), $s['vendor'], 180000, 3);

        Livewire::actingAs($s['buyer'])
            ->test(UserRfqsPage::class)
            ->assertSee('180 000,00');
    }

    #[Test]
    public function an_expired_price_is_flagged_rather_than_advertised(): void
    {
        $s = $this->negotiation();
        (new OrderNegotiation())->price($s['order']->fresh(), $s['vendor'], 180000, 3);
        $s['order']->fresh()->update(['negotiated_expires_at' => now()->subMinute()]);

        Livewire::actingAs($s['buyer'])
            ->test(UserRfqsPage::class)
            ->assertSee('Prix expiré')
            ->assertDontSee('180 000,00');
    }

    #[Test]
    public function cancelling_from_the_list_also_cancels_the_order(): void
    {
        // The bug this covers: the page cancelled the BulkRfq row and left the
        // order in 'negotiating' forever — unpayable, and with no button anywhere
        // left to close it.
        $s = $this->negotiation();

        Livewire::actingAs($s['buyer'])
            ->test(UserRfqsPage::class)
            ->call('cancelRfq', $s['order']->negotiation->id);

        $order = $s['order']->fresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame('cancelled', $order->negotiation->fresh()->status);
    }

    #[Test]
    public function a_buyer_cannot_cancel_someone_elses_negotiation(): void
    {
        $s = $this->negotiation();
        $stranger = User::factory()->create();

        Livewire::actingAs($stranger)
            ->test(UserRfqsPage::class)
            ->call('cancelRfq', $s['order']->negotiation->id);

        $this->assertSame(Order::STATUS_NEGOTIATING, $s['order']->fresh()->status);
    }

    #[Test]
    public function the_search_finds_a_negotiation_by_order_number(): void
    {
        // Searching product names found nothing for a basket negotiation, which
        // has none. The order number is what a buyer has in front of them.
        $s = $this->negotiation();

        Livewire::actingAs($s['buyer'])
            ->test(UserRfqsPage::class)
            ->set('search', $s['order']->order_number)
            ->assertSee('Commande ' . $s['order']->order_number)
            ->assertDontSee('Aucune négociation');
    }

    #[Test]
    public function the_search_finds_a_negotiation_by_shop_name(): void
    {
        $s = $this->negotiation();

        Livewire::actingAs($s['buyer'])
            ->test(UserRfqsPage::class)
            ->set('search', 'Kaloum')
            ->assertSee('Commande ' . $s['order']->order_number);
    }

    #[Test]
    public function a_single_product_request_still_shows_its_product(): void
    {
        // The original quote flow shares this table; it must not have been broken
        // by teaching the rows about orders.
        $s = $this->negotiation();

        BulkRfq::create([
            'user_id' => $s['buyer']->id,
            'vendor_id' => $s['vendor']->id,
            'product_id' => $s['product']->id,
            'order_id' => null,
            'status' => 'pending',
            'quantity' => 500,
            'target_price' => 90000,
            'currency' => 'GNF',
        ]);

        Livewire::actingAs($s['buyer'])
            ->test(UserRfqsPage::class)
            ->assertSee('Article')
            ->assertSee('Commande ' . $s['order']->order_number);
    }

    #[Test]
    public function a_buyer_sees_only_their_own_negotiations(): void
    {
        $s = $this->negotiation();
        $stranger = User::factory()->create();

        Livewire::actingAs($stranger)
            ->test(UserRfqsPage::class)
            ->assertSee('Aucune négociation')
            ->assertDontSee('Commande ' . $s['order']->order_number);
    }
}
