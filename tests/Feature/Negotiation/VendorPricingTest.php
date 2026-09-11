<?php

namespace Tests\Feature\Negotiation;

use App\Filament\Vendor\Resources\OrderResource;
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
 * The vendor answers and names a price from the order they can already see.
 *
 * The earlier RFQ feature put this on a resource hidden from navigation, with no
 * notification of any kind, and no vendor ever quoted anything. These tests pin
 * both halves of the fix: the action exists on the order, and the navigation
 * badge counts buyers waiting on a price.
 */
class VendorPricingTest extends TestCase
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

        return compact('vendor', 'vendorUser', 'buyer', 'order');
    }

    private function actingAsVendor(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        $this->actingAs($user);
    }

    #[Test]
    public function the_vendor_names_a_price_from_their_own_order_page(): void
    {
        $s = $this->scenario();
        $this->actingAsVendor($s['vendorUser']);

        Livewire::test(ViewOrder::class, ['record' => $s['order']->getRouteKey()])
            ->callAction('fixerPrixConvenu', data: [
                'total' => 180000,
                'valid_days' => 3,
                'note' => 'Meilleur prix possible.',
            ])
            ->assertHasNoActionErrors();

        $order = $s['order']->fresh();

        $this->assertSame(Order::NEGOTIATION_PRICED, $order->negotiation_status);
        $this->assertEqualsWithDelta(180000, (float) $order->negotiated_total, 0.01);
        // Naming a price is not agreeing one — the order stays unpayable.
        $this->assertTrue($order->isNegotiating());
    }

    #[Test]
    public function the_price_action_is_hidden_once_the_negotiation_is_over(): void
    {
        $s = $this->scenario();
        (new OrderNegotiation())->cancel($s['order'], $s['buyer']);
        $this->actingAsVendor($s['vendorUser']);

        Livewire::test(ViewOrder::class, ['record' => $s['order']->fresh()->getRouteKey()])
            ->assertActionHidden('fixerPrixConvenu');
    }

    #[Test]
    public function a_vendor_cannot_open_another_shops_negotiation(): void
    {
        // getEloquentQuery() scopes the resource to the signed-in vendor, so the
        // record simply does not resolve for anyone else.
        $s = $this->scenario();
        $intruderUser = User::factory()->create();
        Vendor::factory()->create(['user_id' => $intruderUser->id, 'is_active' => true]);

        $this->actingAsVendor($intruderUser);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(ViewOrder::class, ['record' => $s['order']->getRouteKey()]);
    }

    #[Test]
    public function the_vendors_reply_reaches_the_thread(): void
    {
        $s = $this->scenario();
        $this->actingAsVendor($s['vendorUser']);

        Livewire::test(ViewOrder::class, ['record' => $s['order']->getRouteKey()])
            ->callAction('repondreNegociation', data: ['message' => 'Je peux descendre un peu.'])
            ->assertHasNoActionErrors();

        $this->assertStringContainsString(
            'descendre un peu',
            $s['order']->negotiation->messages()->pluck('message')->implode("\n")
        );
    }

    #[Test]
    public function a_buyer_waiting_on_a_price_shows_on_the_navigation_badge(): void
    {
        // The signal the RFQ feature never had. It counts down to nothing once
        // the vendor has answered, which is what keeps a badge worth reading.
        $s = $this->scenario();
        $this->actingAsVendor($s['vendorUser']);

        $this->assertSame('1', OrderResource::getNavigationBadge());

        (new OrderNegotiation())->price($s['order']->fresh(), $s['vendor'], 180000, 3);

        $this->assertNull(OrderResource::getNavigationBadge());
    }
}
