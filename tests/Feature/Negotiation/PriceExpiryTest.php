<?php

namespace Tests\Feature\Negotiation;

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
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * A price the buyer let lapse.
 *
 * The sweep is bookkeeping, not enforcement, and that distinction is load-bearing
 * on this deployment: nothing runs `php artisan schedule:run` on a timer, because
 * Render binds the persistent disk holding the sqlite database to the single web
 * service (routes/console.php records the same constraint for shipments:sync).
 * So the tests that matter most here are the ones proving an expired price is
 * refused whether or not the command ever ran.
 */
class PriceExpiryTest extends TestCase
{
    use RefreshDatabase;

    private function negotiation(): array
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => 0.00012]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id, 'is_active' => true]);
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

        return compact('vendor', 'buyer', 'order');
    }

    #[Test]
    public function an_expired_price_is_refused_even_when_the_sweep_never_ran(): void
    {
        // The one that matters on a host where the scheduler does not run.
        $s = $this->negotiation();
        $negotiation = new OrderNegotiation();
        $negotiation->price($s['order']->fresh(), $s['vendor'], 180000, 3);
        $s['order']->fresh()->update(['negotiated_expires_at' => now()->subMinute()]);

        $this->expectException(RuntimeException::class);

        $negotiation->accept($s['order']->fresh(), $s['buyer']);
    }

    #[Test]
    public function an_expired_price_is_no_longer_offered_to_the_buyer(): void
    {
        $s = $this->negotiation();
        (new OrderNegotiation())->price($s['order']->fresh(), $s['vendor'], 180000, 3);
        $s['order']->fresh()->update(['negotiated_expires_at' => now()->subMinute()]);

        $order = $s['order']->fresh();

        $this->assertFalse($order->hasLiveOffer());
        $this->assertTrue($order->offerHasExpired());
    }

    #[Test]
    public function the_sweep_reopens_the_discussion(): void
    {
        $s = $this->negotiation();
        (new OrderNegotiation())->price($s['order']->fresh(), $s['vendor'], 180000, 3);
        $s['order']->fresh()->update(['negotiated_expires_at' => now()->subMinute()]);

        $this->artisan('negotiations:expire')->assertExitCode(0);

        $order = $s['order']->fresh();

        $this->assertSame(Order::NEGOTIATION_OPEN, $order->negotiation_status);
        $this->assertNull($order->negotiated_total);
        // The order stays in play — the vendor can name another price.
        $this->assertTrue($order->isNegotiating());
    }

    #[Test]
    public function the_thread_is_told_the_price_lapsed(): void
    {
        $s = $this->negotiation();
        (new OrderNegotiation())->price($s['order']->fresh(), $s['vendor'], 180000, 3);
        $s['order']->fresh()->update(['negotiated_expires_at' => now()->subMinute()]);

        $this->artisan('negotiations:expire');

        $this->assertStringContainsString(
            'a expiré',
            $s['order']->negotiation->messages()->pluck('message')->implode("\n")
        );
    }

    #[Test]
    public function the_sweep_leaves_a_price_that_is_still_valid_alone(): void
    {
        $s = $this->negotiation();
        (new OrderNegotiation())->price($s['order']->fresh(), $s['vendor'], 180000, 3);

        $this->artisan('negotiations:expire');

        $this->assertTrue($s['order']->fresh()->hasLiveOffer());
    }

    #[Test]
    public function the_sweep_leaves_settled_orders_alone(): void
    {
        $s = $this->negotiation();
        $negotiation = new OrderNegotiation();
        $negotiation->price($s['order']->fresh(), $s['vendor'], 180000, 3);
        $negotiation->accept($s['order']->fresh(), $s['buyer']);

        $this->artisan('negotiations:expire');

        $order = $s['order']->fresh();

        $this->assertSame('new', $order->status);
        $this->assertEqualsWithDelta(180000, (float) $order->grand_total, 0.01);
    }

    #[Test]
    public function the_vendor_can_price_again_after_an_expiry(): void
    {
        $s = $this->negotiation();
        $negotiation = new OrderNegotiation();
        $negotiation->price($s['order']->fresh(), $s['vendor'], 180000, 3);
        $s['order']->fresh()->update(['negotiated_expires_at' => now()->subMinute()]);
        $this->artisan('negotiations:expire');

        $repriced = $negotiation->price($s['order']->fresh(), $s['vendor'], 175000, 5);

        $this->assertTrue($repriced->hasLiveOffer());
        $this->assertEqualsWithDelta(175000, (float) $repriced->negotiated_total, 0.01);
    }
}
