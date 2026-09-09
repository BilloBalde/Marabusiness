<?php

namespace Tests\Feature\Rfq;

use App\Filament\Vendor\Resources\BulkRfqResource\Pages\QuoteBulkRfq;
use App\Mail\RfqOfferAccepted;
use App\Mail\RfqOfferRejected;
use App\Mail\RfqQuoteReceived;
use App\Models\Brand;
use App\Models\BulkRfq;
use App\Models\BulkRfqOffer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\FinancialTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\RfqOfferConverter;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Covers the half of the RFQ feature that did not exist: accepting a vendor's quote and
 * turning it into a payable order. Before this, offers could be created but never moved
 * out of "pending" — the negotiation had no ending.
 */
class OfferAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(array $offerOverrides = [], float $vendorRateToUsd = 1.0): array
    {
        $vendorCurrency = Currency::factory()->create([
            'code' => 'VND' . random_int(100, 999),
            'rate_to_usd' => $vendorRateToUsd,
        ]);
        $usd = Currency::factory()->create(['code' => 'USD', 'rate_to_usd' => 1]);

        $vendor = Vendor::factory()->create(['currency_id' => $vendorCurrency->id]);
        $buyer = User::factory()->create();

        // products requires both category_id and brand_id at the database level.
        $category = Category::create(['name' => 'Catégorie RFQ', 'slug' => 'cat-rfq-' . uniqid()]);
        $brand = Brand::create(['name' => 'Marque RFQ', 'slug' => 'marque-rfq-' . uniqid()]);

        $product = Product::create([
            'name' => 'Produit RFQ',
            'slug' => 'produit-rfq-' . uniqid(),
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        $rfq = BulkRfq::create([
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'status' => 'quoted',
            'quantity' => 500,
            'currency' => 'USD',
            'shipping_country' => 'Guinea',
            'shipping_city' => 'Conakry',
        ]);

        $offer = BulkRfqOffer::create(array_merge([
            'bulk_rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id,
            'moq' => 500,
            'unit_price' => 11.75,
            'currency' => 'USD',
            'lead_time_days' => 30,
            'shipping_terms' => 'FOB',
            'shipping_cost' => 250,
            'status' => BulkRfqOffer::STATUS_PENDING,
        ], $offerOverrides));

        return compact('vendor', 'buyer', 'rfq', 'offer', 'usd');
    }

    #[Test]
    public function accepting_an_offer_creates_a_payable_order_at_the_negotiated_price(): void
    {
        ['offer' => $offer, 'buyer' => $buyer, 'vendor' => $vendor] = $this->scenario();

        $order = (new RfqOfferConverter())->accept($offer, $buyer);

        $this->assertSame($buyer->id, $order->user_id);
        $this->assertSame($vendor->id, $order->vendor_id);
        $this->assertSame('new', $order->status);
        $this->assertSame('pending', $order->payment_status);

        // 500 × 11.75 + 250 shipping = 6 125
        $this->assertSame(6125.0, (float) $order->grand_total_usd);

        // Payable means the "Payer" button on the orders list shows it, which keys off
        // the remaining balance.
        $this->assertGreaterThan(0, (float) $order->total_remaining);
    }

    #[Test]
    public function the_order_carries_the_quoted_quantity_and_an_address(): void
    {
        ['offer' => $offer, 'buyer' => $buyer] = $this->scenario();

        $order = (new RfqOfferConverter())->accept($offer, $buyer);
        $item = $order->items()->first();

        $this->assertSame(500, $item->quantity);
        $this->assertSame(5875.0, (float) $item->total_amount); // 500 × 11.75, hors port

        // Orders are undeliverable without an address row; the RFQ's shipping details
        // are used when the buyer has no saved address.
        $this->assertNotNull($order->address);
        $this->assertSame('Conakry', $order->address->city);
    }

    #[Test]
    public function a_quote_in_another_currency_is_converted_into_the_vendors_currency(): void
    {
        // Vendor bills in a currency worth 0.5 USD, so 6 125 USD becomes 12 250 of it.
        ['offer' => $offer, 'buyer' => $buyer] = $this->scenario(vendorRateToUsd: 0.5);

        $order = (new RfqOfferConverter())->accept($offer, $buyer);

        $this->assertSame(6125.0, (float) $order->grand_total_usd);
        $this->assertSame(12250.0, (float) $order->grand_total);
        $this->assertSame(500.0, (float) $order->shipping_amount); // 250 USD ÷ 0,5
    }

    #[Test]
    public function accepting_advances_both_the_offer_and_the_request(): void
    {
        ['offer' => $offer, 'buyer' => $buyer, 'rfq' => $rfq] = $this->scenario();

        (new RfqOfferConverter())->accept($offer, $buyer);

        $this->assertSame(BulkRfqOffer::STATUS_ACCEPTED, $offer->fresh()->status);
        $this->assertSame('accepted', $rfq->fresh()->status);
    }

    #[Test]
    public function the_other_quotes_on_the_same_request_are_set_aside(): void
    {
        ['offer' => $offer, 'buyer' => $buyer, 'rfq' => $rfq, 'vendor' => $vendor] = $this->scenario();

        $rival = BulkRfqOffer::create([
            'bulk_rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id,
            'moq' => 400,
            'unit_price' => 13,
            'currency' => 'USD',
            'lead_time_days' => 20,
            'shipping_terms' => 'FOB',
            'shipping_cost' => 100,
            'status' => BulkRfqOffer::STATUS_PENDING,
        ]);

        (new RfqOfferConverter())->accept($offer, $buyer);

        $this->assertSame(BulkRfqOffer::STATUS_REJECTED, $rival->fresh()->status);
    }

    #[Test]
    public function the_vendors_commission_breakdown_is_recorded(): void
    {
        ['offer' => $offer, 'buyer' => $buyer] = $this->scenario();

        $order = (new RfqOfferConverter())->accept($offer, $buyer);

        $this->assertGreaterThan(0, FinancialTransaction::where('order_id', $order->id)->count());
    }

    #[Test]
    public function the_buyer_is_told_in_the_conversation(): void
    {
        ['offer' => $offer, 'buyer' => $buyer, 'rfq' => $rfq] = $this->scenario();

        $order = (new RfqOfferConverter())->accept($offer, $buyer);

        $this->assertStringContainsString(
            $order->order_number,
            $rfq->messages()->latest()->first()->message
        );
    }

    // --- Garde-fous ---------------------------------------------------------

    #[Test]
    public function the_same_offer_cannot_be_accepted_twice(): void
    {
        ['offer' => $offer, 'buyer' => $buyer] = $this->scenario();

        (new RfqOfferConverter())->accept($offer, $buyer);

        $this->expectException(RuntimeException::class);
        (new RfqOfferConverter())->accept($offer->fresh(), $buyer);
    }

    #[Test]
    public function another_buyer_cannot_accept_someone_elses_quote(): void
    {
        ['offer' => $offer] = $this->scenario();
        $intruder = User::factory()->create();

        $this->expectException(RuntimeException::class);
        (new RfqOfferConverter())->accept($offer, $intruder);
    }

    #[Test]
    public function a_quote_in_an_unknown_currency_is_refused_rather_than_mispriced(): void
    {
        // The quote form used to offer EUR, which has no rate in the currencies table:
        // converting it would have invented a price.
        ['offer' => $offer, 'buyer' => $buyer] = $this->scenario(['currency' => 'EUR']);

        $this->expectException(RuntimeException::class);
        (new RfqOfferConverter())->accept($offer, $buyer);
    }

    #[Test]
    public function nothing_is_written_when_acceptance_fails(): void
    {
        ['offer' => $offer, 'buyer' => $buyer] = $this->scenario(['currency' => 'EUR']);

        try {
            (new RfqOfferConverter())->accept($offer, $buyer);
        } catch (RuntimeException) {
            // expected
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(BulkRfqOffer::STATUS_PENDING, $offer->fresh()->status);
    }

    // --- Refus --------------------------------------------------------------

    #[Test]
    public function rejecting_a_quote_reopens_the_request_so_the_vendor_can_requote(): void
    {
        ['offer' => $offer, 'buyer' => $buyer, 'rfq' => $rfq] = $this->scenario();

        (new RfqOfferConverter())->reject($offer, $buyer, 'Prix trop élevé.');

        $this->assertSame(BulkRfqOffer::STATUS_REJECTED, $offer->fresh()->status);
        // The vendor's quote screen only accepts a request that is back to "pending".
        $this->assertSame('pending', $rfq->fresh()->status);
        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function the_request_stays_open_while_another_quote_is_still_standing(): void
    {
        ['offer' => $offer, 'buyer' => $buyer, 'rfq' => $rfq, 'vendor' => $vendor] = $this->scenario();

        BulkRfqOffer::create([
            'bulk_rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id,
            'moq' => 400,
            'unit_price' => 13,
            'currency' => 'USD',
            'lead_time_days' => 20,
            'shipping_terms' => 'FOB',
            'shipping_cost' => 100,
            'status' => BulkRfqOffer::STATUS_PENDING,
        ]);

        (new RfqOfferConverter())->reject($offer, $buyer, null);

        $this->assertSame('quoted', $rfq->fresh()->status);
    }

    #[Test]
    public function the_reason_reaches_the_conversation(): void
    {
        ['offer' => $offer, 'buyer' => $buyer, 'rfq' => $rfq] = $this->scenario();

        (new RfqOfferConverter())->reject($offer, $buyer, 'Délai trop long.');

        $this->assertStringContainsString(
            'Délai trop long.',
            $rfq->messages()->latest()->first()->message
        );
    }

    #[Test]
    public function another_buyer_cannot_reject_someone_elses_quote(): void
    {
        ['offer' => $offer] = $this->scenario();

        $this->expectException(RuntimeException::class);
        (new RfqOfferConverter())->reject($offer, User::factory()->create());
    }

    // --- Notifications au vendeur -------------------------------------------

    #[Test]
    public function the_vendor_is_emailed_when_their_quote_is_accepted(): void
    {
        Mail::fake();
        ['offer' => $offer, 'buyer' => $buyer, 'vendor' => $vendor] = $this->scenario();

        (new RfqOfferConverter())->accept($offer, $buyer);

        Mail::assertSent(
            RfqOfferAccepted::class,
            fn (RfqOfferAccepted $mail) => $mail->hasTo($vendor->user->email)
        );
    }

    #[Test]
    public function the_vendor_is_emailed_when_their_quote_is_rejected(): void
    {
        Mail::fake();
        ['offer' => $offer, 'buyer' => $buyer, 'vendor' => $vendor] = $this->scenario();

        (new RfqOfferConverter())->reject($offer, $buyer, 'Trop cher.');

        Mail::assertSent(RfqOfferRejected::class, function (RfqOfferRejected $mail) use ($vendor) {
            return $mail->hasTo($vendor->user->email)
                && $mail->reason === 'Trop cher.'
                && $mail->canRequote === true;
        });
    }

    #[Test]
    public function both_notification_templates_actually_render(): void
    {
        // Mail::fake() never renders the view, so a broken template would only surface
        // in production. These two are rendered for real.
        ['offer' => $offer, 'buyer' => $buyer] = $this->scenario();

        $order = (new RfqOfferConverter())->accept($offer, $buyer);

        $accepted = (new RfqOfferAccepted($offer->fresh(), $order))->render();
        $this->assertStringContainsString($order->order_number, $accepted);

        $rejected = (new RfqOfferRejected($offer->fresh(), 'Trop cher.', true))->render();
        $this->assertStringContainsString('Trop cher.', $rejected);
    }

    #[Test]
    public function the_buyer_is_emailed_when_a_vendor_submits_a_quote(): void
    {
        Mail::fake();
        ['rfq' => $rfq, 'vendor' => $vendor, 'buyer' => $buyer] = $this->scenario();
        $rfq->update(['status' => 'pending']); // the quote screen only opens on a pending request

        // BulkRfqResource lives in the vendor panel only. Livewire::test() renders
        // outside any panel, so its getUrl() calls would resolve against the admin
        // panel and fail on a route that exists only for vendors.
        Filament::setCurrentPanel(Filament::getPanel('vendor'));

        Livewire::actingAs($vendor->user)
            ->test(QuoteBulkRfq::class, ['record' => $rfq->id])
            ->fillForm([
                'moq' => 200,
                'unit_price' => 9.5,
                'currency' => 'USD',
                'lead_time_days' => 25,
                'shipping_terms' => 'CIF',
                'shipping_cost' => 150,
                'vendor_notes' => 'Remise possible au-delà de 500 unités.',
            ])
            ->call('submitQuote');

        Mail::assertSent(
            RfqQuoteReceived::class,
            fn (RfqQuoteReceived $mail) => $mail->hasTo($buyer->email)
        );
    }

    #[Test]
    public function the_quote_notification_template_renders(): void
    {
        ['offer' => $offer] = $this->scenario(['vendor_notes' => 'Remise au-delà de 500.']);

        $html = (new RfqQuoteReceived($offer))->render();

        $this->assertStringContainsString('11.75', $html);
        $this->assertStringContainsString('Remise au-delà de 500.', $html);
        $this->assertStringContainsString('/rfq/' . $offer->bulk_rfq_id . '/chat', $html);
    }

    #[Test]
    public function a_failing_mail_server_does_not_undo_the_order(): void
    {
        ['offer' => $offer, 'buyer' => $buyer] = $this->scenario();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP indisponible'));

        $order = (new RfqOfferConverter())->accept($offer, $buyer);

        // The sale is what matters; the vendor also sees the decision in the chat.
        $this->assertNotNull($order->fresh());
        $this->assertSame(BulkRfqOffer::STATUS_ACCEPTED, $offer->fresh()->status);
    }
}
