<?php

namespace Tests\Feature\Negotiation;

use App\Models\Brand;
use App\Models\BulkRfq;
use App\Models\Category;
use App\Models\Currency;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\OrderNegotiation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * A buyer opens a price discussion on one vendor's share of their basket. The
 * order exists from the first message so both sides have something concrete to
 * talk about, but nothing may be collected against it until a price is agreed.
 *
 * The conversation reuses BulkRfq and BulkRfqMessage — the /rfq/{id}/chat screen
 * works for these threads unchanged. BulkRfqOffer is not used: its unit_price is
 * NOT NULL and pairs with a MOQ, which describes a single-product quote and not a
 * basket.
 */
class OrderNegotiationTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(float $rateToUsd = 0.00012): array
    {
        $currency = Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => $rateToUsd]);
        $vendor = Vendor::factory()->create(['currency_id' => $currency->id, 'is_active' => true]);
        $buyer = User::factory()->create();

        $product = Product::create([
            'name' => 'Article négocié',
            'slug' => 'article-negocie-' . uniqid(),
            'category_id' => Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'is_active' => true,
        ]);

        $items = [[
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'quantity' => 2,
            'unit_amount' => 100000,
            'total_amount' => 200000,
        ]];

        return compact('vendor', 'buyer', 'product', 'items', 'currency');
    }

    /** The vendor's listing for the scenario product, so stock can be observed. */
    private function listing(array $s, int $stock): \App\Models\VendorProduct
    {
        return \App\Models\VendorProduct::create([
            'vendor_id' => $s['vendor']->id,
            'product_id' => $s['product']->id,
            'price' => 100000,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    private function open(array $s, ?float $target = 150000): Order
    {
        return (new OrderNegotiation())->open(
            buyer: $s['buyer'],
            vendor: $s['vendor'],
            items: $s['items'],
            shippingLocal: 20000,
            shippingUsd: 2.4,
            targetPrice: $target,
            message: 'Bonjour, pouvez-vous faire un geste sur ce panier ?',
            address: [
                'first_name' => 'A',
                'last_name' => 'B',
                'city' => 'Conakry',
                'country' => 'Guinea',
                // addresses.phone and street_address are NOT NULL.
                'phone' => '600000000',
                'street_address' => 'Rue 1',
                'state' => 'Conakry',
            ],
        );
    }

    #[Test]
    public function opening_a_negotiation_creates_an_order_that_cannot_be_paid(): void
    {
        $order = $this->open($this->scenario());

        $this->assertSame(Order::STATUS_NEGOTIATING, $order->status);
        $this->assertSame(Order::NEGOTIATION_OPEN, $order->negotiation_status);
        $this->assertTrue($order->isNegotiating());
        $this->assertSame('pending', $order->payment_status);

        // Items plus shipping, kept twice: grand_total moves when a price is
        // agreed, pre_negotiation_total records what it would otherwise cost.
        $this->assertEqualsWithDelta(220000, (float) $order->grand_total, 0.01);
        $this->assertEqualsWithDelta(220000, (float) $order->pre_negotiation_total, 0.01);
        $this->assertCount(1, $order->items);
    }

    #[Test]
    public function opening_a_negotiation_starts_a_thread_carrying_the_buyers_target(): void
    {
        $order = $this->open($this->scenario(), target: 150000);

        $rfq = BulkRfq::where('order_id', $order->id)->firstOrFail();

        $this->assertEqualsWithDelta(150000, (float) $rfq->target_price, 0.01);
        $this->assertSame('pending', $rfq->status);
        $this->assertCount(1, $rfq->messages);
        // A basket spans several products, so naming one here would be a lie.
        $this->assertNull($rfq->product_id);
    }

    #[Test]
    public function nothing_reaches_the_books_before_a_price_is_agreed(): void
    {
        // Writing revenue and commission rows against a provisional figure would
        // put a number nobody has accepted into the vendor's accounts.
        $order = $this->open($this->scenario());

        $this->assertSame(0, FinancialTransaction::where('order_id', $order->id)->count());
    }

    #[Test]
    public function the_vendor_names_a_price_and_it_waits_on_the_buyer(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);

        $priced = (new OrderNegotiation())->price($order, $s['vendor'], 180000, 3, 'Meilleur prix possible.');

        $this->assertSame(Order::NEGOTIATION_PRICED, $priced->negotiation_status);
        $this->assertEqualsWithDelta(180000, (float) $priced->negotiated_total, 0.01);
        $this->assertTrue($priced->hasLiveOffer());
        // Still not payable: naming a price is not agreeing one.
        $this->assertTrue($priced->isNegotiating());
        // The old total survives so a refusal can fall back to it.
        $this->assertEqualsWithDelta(220000, (float) $priced->pre_negotiation_total, 0.01);
    }

    #[Test]
    public function a_vendor_cannot_price_another_shops_order(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);
        $intruder = Vendor::factory()->create(['currency_id' => $s['currency']->id]);

        $this->expectException(RuntimeException::class);

        (new OrderNegotiation())->price($order, $intruder, 1, 3);
    }

    #[Test]
    public function accepting_applies_the_price_and_makes_the_order_payable(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);
        $negotiation = new OrderNegotiation();
        $negotiation->price($order->fresh(), $s['vendor'], 180000, 3);

        $accepted = $negotiation->accept($order->fresh(), $s['buyer']);

        $this->assertSame('new', $accepted->status);
        $this->assertSame(Order::NEGOTIATION_AGREED, $accepted->negotiation_status);
        $this->assertFalse($accepted->isNegotiating());
        $this->assertEqualsWithDelta(180000, (float) $accepted->grand_total, 0.01);
        $this->assertEqualsWithDelta(180000, (float) $accepted->total_remaining, 0.01);
        // 180,000 GNF at 0.00012 to the dollar.
        $this->assertEqualsWithDelta(21.6, (float) $accepted->grand_total_usd, 0.01);
    }

    #[Test]
    public function the_books_are_written_only_once_both_sides_agree(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);
        $negotiation = new OrderNegotiation();
        $negotiation->price($order->fresh(), $s['vendor'], 180000, 3);
        $negotiation->accept($order->fresh(), $s['buyer']);

        $this->assertGreaterThan(0, FinancialTransaction::where('order_id', $order->id)->count());
    }

    #[Test]
    public function a_price_that_has_run_out_cannot_be_accepted(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);
        $negotiation = new OrderNegotiation();
        $negotiation->price($order->fresh(), $s['vendor'], 180000, 3);

        // Reach past the service to age the offer, the way time would.
        $order->fresh()->update(['negotiated_expires_at' => now()->subDay()]);

        $this->expectException(RuntimeException::class);

        $negotiation->accept($order->fresh(), $s['buyer']);
    }

    #[Test]
    public function accepting_before_any_price_exists_is_refused(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);

        $this->expectException(RuntimeException::class);

        (new OrderNegotiation())->accept($order, $s['buyer']);
    }

    #[Test]
    public function another_buyer_cannot_accept_someone_elses_price(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);
        $negotiation = new OrderNegotiation();
        $negotiation->price($order->fresh(), $s['vendor'], 180000, 3);

        $this->expectException(RuntimeException::class);

        $negotiation->accept($order->fresh(), User::factory()->create());
    }

    #[Test]
    public function refusing_reopens_the_discussion_rather_than_ending_it(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);
        $negotiation = new OrderNegotiation();
        $negotiation->price($order->fresh(), $s['vendor'], 180000, 3);

        $refused = $negotiation->refuse($order->fresh(), $s['buyer'], 'Encore un peu trop cher');

        $this->assertSame(Order::NEGOTIATION_OPEN, $refused->negotiation_status);
        $this->assertNull($refused->negotiated_total);
        // The order is still in play — the vendor can name another price.
        $this->assertTrue($refused->isNegotiating());
        $this->assertSame('pending', BulkRfq::where('order_id', $order->id)->value('status'));
    }

    #[Test]
    public function the_reason_for_a_refusal_reaches_the_conversation(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);
        $negotiation = new OrderNegotiation();
        $negotiation->price($order->fresh(), $s['vendor'], 180000, 3);
        $negotiation->refuse($order->fresh(), $s['buyer'], 'Encore un peu trop cher');

        $rfq = BulkRfq::where('order_id', $order->id)->firstOrFail();

        // Asserted against the whole thread rather than latest(): several
        // messages land in the same second, and ordering by created_at alone is
        // then arbitrary.
        $this->assertStringContainsString(
            'Encore un peu trop cher',
            $rfq->messages()->pluck('message')->implode("\n")
        );
    }

    #[Test]
    public function the_vendor_can_price_again_after_a_refusal(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);
        $negotiation = new OrderNegotiation();
        $negotiation->price($order->fresh(), $s['vendor'], 180000, 3);
        $negotiation->refuse($order->fresh(), $s['buyer']);

        $repriced = $negotiation->price($order->fresh(), $s['vendor'], 165000, 3);

        $this->assertEqualsWithDelta(165000, (float) $repriced->negotiated_total, 0.01);
    }

    #[Test]
    public function cancelling_closes_the_order_and_records_why(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);

        $cancelled = (new OrderNegotiation())->cancel($order, $s['buyer'], 'Trouvé ailleurs');

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertNull($cancelled->negotiation_status);
        // cancelled_at and cancellation_reason were not fillable until now, so
        // every cancelled order in the database carries neither.
        $this->assertNotNull($cancelled->cancelled_at);
        $this->assertSame('Trouvé ailleurs', $cancelled->cancellation_reason);
    }

    #[Test]
    public function accepting_spreads_the_agreed_price_across_the_lines(): void
    {
        // EditOrder::recalculateOrderTotals() recomputes grand_total as
        // sum(order_items.total_amount) + shipping every time anyone saves the
        // order in Filament. Leaving the lines at their catalogue price while
        // writing the negotiated figure onto the order means the first back-office
        // save silently reverts the discount.
        $s = $this->scenario();
        $order = $this->open($s);
        $negotiation = new OrderNegotiation();
        $negotiation->price($order->fresh(), $s['vendor'], 180000, 3);
        $accepted = $negotiation->accept($order->fresh(), $s['buyer']);

        $goods = (float) $accepted->items()->sum('total_amount');

        // 180,000 agreed minus 20,000 shipping.
        $this->assertEqualsWithDelta(160000, $goods, 0.01);
        // The invariant that matters: recomputing from the lines reproduces the
        // agreed total exactly.
        $this->assertEqualsWithDelta(
            (float) $accepted->grand_total,
            $goods + (float) $accepted->shipping_amount,
            0.01
        );
    }

    #[Test]
    public function the_rounding_residual_keeps_the_line_sum_exact(): void
    {
        // Three lines and a total that does not divide evenly: without pushing the
        // residual onto one line, the sum drifts a few minor units from the agreed
        // figure and the next Filament save writes that drift back as the total.
        $s = $this->scenario();
        $s['items'] = array_map(fn ($n) => [
            'product_id' => $s['product']->id,
            'vendor_id' => $s['vendor']->id,
            'quantity' => 3,
            'unit_amount' => $n,
            'total_amount' => $n * 3,
        ], [1111, 2222, 3333]);

        $order = $this->open($s);
        $negotiation = new OrderNegotiation();
        $negotiation->price($order->fresh(), $s['vendor'], 100000, 3);
        $accepted = $negotiation->accept($order->fresh(), $s['buyer']);

        $this->assertEqualsWithDelta(
            80000,
            (float) $accepted->items()->sum('total_amount'),
            0.01,
            '100,000 agreed minus 20,000 shipping, to the minor unit'
        );
    }

    #[Test]
    public function stock_is_left_alone_while_the_price_is_discussed(): void
    {
        // The owner chose not to hold stock during a discussion that may never
        // conclude.
        $s = $this->scenario();
        $listing = $this->listing($s, stock: 10);

        $this->open($s);

        $this->assertSame(10, (int) $listing->fresh()->stock);
    }

    #[Test]
    public function accepting_takes_the_goods_off_the_shelf(): void
    {
        $s = $this->scenario();
        $listing = $this->listing($s, stock: 10);
        $order = $this->open($s);
        $negotiation = new OrderNegotiation();
        $negotiation->price($order->fresh(), $s['vendor'], 180000, 3);
        $negotiation->accept($order->fresh(), $s['buyer']);

        // An ordinary order decrements at placement; acceptance is the equivalent
        // moment for a negotiated one.
        $this->assertSame(8, (int) $listing->fresh()->stock);
    }

    #[Test]
    public function a_price_agreed_on_goods_that_have_sold_out_is_refused(): void
    {
        $s = $this->scenario();
        $listing = $this->listing($s, stock: 10);
        $order = $this->open($s);
        $negotiation = new OrderNegotiation();
        $negotiation->price($order->fresh(), $s['vendor'], 180000, 3);

        $listing->update(['stock' => 1]);

        try {
            $negotiation->accept($order->fresh(), $s['buyer']);
            $this->fail('a sold-out order should not be accepted');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Stock insuffisant', $e->getMessage());
        }

        // Nothing moved: the order is still under negotiation and the shelf is
        // untouched.
        $this->assertTrue($order->fresh()->isNegotiating());
        $this->assertSame(1, (int) $listing->fresh()->stock);
    }

    #[Test]
    public function a_settled_order_cannot_be_dragged_back_into_negotiation(): void
    {
        $s = $this->scenario();
        $order = $this->open($s);
        $order->update(['payment_status' => 'paid']);

        $this->expectException(RuntimeException::class);

        (new OrderNegotiation())->price($order->fresh(), $s['vendor'], 1000, 3);
    }
}
