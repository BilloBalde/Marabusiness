<?php

namespace Tests\Feature\Negotiation;

use App\Filament\Vendor\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Vendor\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Vendor\Resources\OrderResource\Pages\ViewOrder;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Services\OrderNegotiation;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Where the vendor finds the buyers waiting on a price.
 *
 * The navigation badge already counted them, but the orders list had no tab for
 * 'negotiating' — the badge pointed at a list where those orders appeared only
 * under "All", mixed in with everything else. Worse, the status control in both
 * the table and the edit form omitted 'negotiating' entirely: the field rendered
 * empty and any save moved the order to another status, ending the discussion by
 * accident and making it payable at a price nobody had agreed to.
 */
class VendorNegotiationListTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
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
            'name' => 'Article négocié',
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
            message: 'Un geste possible ?',
            address: [
                'first_name' => 'A', 'last_name' => 'B', 'phone' => '600000000',
                'street_address' => 'Rue 1', 'city' => 'Conakry', 'state' => 'Conakry',
                'country' => 'Guinea',
            ],
        );

        return compact('vendor', 'vendorUser', 'buyer', 'order', 'product');
    }

    private function actingAsVendor(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        $this->actingAs($user);
    }

    /** An ordinary order of the same vendor, to prove the tab actually filters. */
    private function plainOrder(Vendor $vendor, User $buyer): Order
    {
        return Order::create([
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'ORD-PLAIN-' . uniqid(),
            'grand_total' => 50000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'new',
            'shipping_amount' => 0,
            'rate_to_usd' => 0.00012,
        ]);
    }

    #[Test]
    public function the_negotiations_tab_shows_only_orders_under_discussion(): void
    {
        $s = $this->scenario();
        $plain = $this->plainOrder($s['vendor'], $s['buyer']);
        $this->actingAsVendor($s['vendorUser']);

        Livewire::test(ListOrders::class)
            ->set('activeTab', 'negotiating')
            ->assertCanSeeTableRecords([$s['order']])
            ->assertCanNotSeeTableRecords([$plain]);
    }

    #[Test]
    public function the_tab_counts_the_buyers_actually_waiting_on_a_price(): void
    {
        // Same number the navigation badge shows: open, not yet priced.
        $s = $this->scenario();
        $this->actingAsVendor($s['vendorUser']);

        $tabs = Livewire::test(ListOrders::class)->instance()->getTabs();

        $this->assertArrayHasKey('negotiating', $tabs);
        $this->assertSame('1', (string) $tabs['negotiating']->getBadge());
    }

    #[Test]
    public function the_count_clears_once_the_vendor_has_named_a_price(): void
    {
        $s = $this->scenario();
        (new OrderNegotiation())->price($s['order'], $s['vendor'], 180000, 3);
        $this->actingAsVendor($s['vendorUser']);

        $tabs = Livewire::test(ListOrders::class)->instance()->getTabs();

        // The order is still in the tab; it just no longer asks anything of the
        // vendor, so it stops being counted.
        $this->assertNull($tabs['negotiating']->getBadge());

        Livewire::test(ListOrders::class)
            ->set('activeTab', 'negotiating')
            ->assertCanSeeTableRecords([$s['order']]);
    }

    #[Test]
    public function a_price_the_buyer_let_lapse_is_counted_again(): void
    {
        // The case the count used to miss. negotiations:expire would normally
        // move it back to 'open', but nothing runs the scheduler on this host, so
        // the order sits at 'priced' past its expiry: the buyer can no longer
        // accept it and the vendor was never told to name another. A deadlock
        // with no signal anywhere.
        $s = $this->scenario();
        (new OrderNegotiation())->price($s['order'], $s['vendor'], 180000, 3);
        $s['order']->fresh()->update(['negotiated_expires_at' => now()->subMinute()]);

        $this->actingAsVendor($s['vendorUser']);

        $tabs = Livewire::test(ListOrders::class)->instance()->getTabs();

        $this->assertSame('1', (string) $tabs['negotiating']->getBadge());
    }

    #[Test]
    public function an_accepted_negotiation_asks_nothing_more_of_the_vendor(): void
    {
        $s = $this->scenario();
        $negotiation = new OrderNegotiation();
        $negotiation->price($s['order'], $s['vendor'], 180000, 3);
        $negotiation->accept($s['order']->fresh(), $s['buyer']);

        $this->actingAsVendor($s['vendorUser']);

        $tabs = Livewire::test(ListOrders::class)->instance()->getTabs();

        $this->assertNull($tabs['negotiating']->getBadge());
    }

    #[Test]
    public function a_vendor_sees_no_other_shops_negotiations_in_the_tab(): void
    {
        $s = $this->scenario();
        $intruderUser = User::factory()->create();
        Vendor::factory()->create(['user_id' => $intruderUser->id, 'is_active' => true]);

        $this->actingAsVendor($intruderUser);

        Livewire::test(ListOrders::class)
            ->set('activeTab', 'negotiating')
            ->assertCanNotSeeTableRecords([$s['order']]);
    }

    #[Test]
    public function saving_the_edit_form_cannot_end_a_negotiation_by_accident(): void
    {
        // The status field is disabled while negotiating, so Filament does not
        // dehydrate it and the column survives a save untouched.
        $s = $this->scenario();
        $this->actingAsVendor($s['vendorUser']);

        Livewire::test(EditOrder::class, ['record' => $s['order']->getRouteKey()])
            ->assertFormFieldIsDisabled('status')
            ->call('save');

        $order = $s['order']->fresh();

        $this->assertSame(Order::STATUS_NEGOTIATING, $order->status);
        $this->assertSame(Order::NEGOTIATION_OPEN, $order->negotiation_status);
    }

    #[Test]
    public function the_order_page_says_what_the_buyer_asked_for(): void
    {
        // La ressource n'a pas d'infolist : la page « Voir » rend le formulaire en
        // lecture seule, et aucun de ses champs ne parle de la négociation.
        $s = $this->scenario();
        $this->actingAsVendor($s['vendorUser']);

        $subheading = Livewire::test(ViewOrder::class, ['record' => $s['order']->getRouteKey()])
            ->instance()
            ->getSubheading();

        $this->assertStringContainsString('Souhait du client', $subheading);
        // Même mise en forme que priceContext() juste à côté : number_format par
        // défaut, comme partout dans ce panneau.
        $this->assertStringContainsString('150,000.00', $subheading);
        $this->assertStringContainsString('En attente de votre prix', $subheading);
    }

    #[Test]
    public function the_order_page_shows_the_price_the_vendor_named_and_its_expiry(): void
    {
        $s = $this->scenario();
        (new OrderNegotiation())->price($s['order'], $s['vendor'], 180000, 3);
        $this->actingAsVendor($s['vendorUser']);

        $subheading = Livewire::test(ViewOrder::class, ['record' => $s['order']->fresh()->getRouteKey()])
            ->instance()
            ->getSubheading();

        $this->assertStringContainsString('Votre prix', $subheading);
        $this->assertStringContainsString('valable jusqu', $subheading);
    }

    #[Test]
    public function an_ordinary_order_keeps_its_usual_subheading(): void
    {
        $s = $this->scenario();
        $plain = $this->plainOrder($s['vendor'], $s['buyer']);
        $this->actingAsVendor($s['vendorUser']);

        $subheading = Livewire::test(ViewOrder::class, ['record' => $plain->getRouteKey()])
            ->instance()
            ->getSubheading();

        $this->assertNull($subheading);
    }

    #[Test]
    public function the_status_field_is_editable_again_on_an_ordinary_order(): void
    {
        $s = $this->scenario();
        $plain = $this->plainOrder($s['vendor'], $s['buyer']);
        $this->actingAsVendor($s['vendorUser']);

        Livewire::test(EditOrder::class, ['record' => $plain->getRouteKey()])
            ->assertFormFieldIsEnabled('status');
    }
}
