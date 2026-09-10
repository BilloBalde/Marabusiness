<?php

namespace Tests\Feature\Integrity;

use App\Models\Currency;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pins the rules every hand-written conversion in the codebase disagreed on.
 */
class MoneyConversionTest extends TestCase
{
    use RefreshDatabase;

    private const GNF = 0.00012;

    #[Test]
    public function a_local_amount_multiplies_into_dollars(): void
    {
        // 50,000 GNF is $6 — the figure that sorted fourth in a "cheapest first"
        // list because the raw number was compared instead.
        $this->assertSame(6.0, round(Money::toUsd(50000, self::GNF), 2));
    }

    #[Test]
    public function a_dollar_amount_divides_back_into_the_local_currency(): void
    {
        $this->assertSame(50000.0, round(Money::fromUsd(6, self::GNF), 0));
    }

    #[Test]
    public function converting_there_and_back_returns_the_original(): void
    {
        $this->assertSame(1234.56, round(Money::fromUsd(Money::toUsd(1234.56, self::GNF), self::GNF), 2));
    }

    #[Test]
    public function a_zero_rate_passes_the_amount_through_instead_of_throwing(): void
    {
        // Each site guarded this differently, or not at all. CheckoutPage and
        // CheckoutController each carried their own identical copy of the guard;
        // RfqOfferConverter threw instead; SuccessPage:94 and
        // SuccessPageStripe:111 wrote `$rate = ... ?? 1`, which catches a missing
        // currency row but not a rate of zero — and zero is a fatal
        // DivisionByZeroError in PHP 8, raised while recording a Stripe payment.
        // The admin form refuses a zero rate now, so this is the second line of
        // defence rather than the only one.
        $this->assertSame(500.0, Money::fromUsd(500, 0.0));
        $this->assertSame(500.0, Money::toUsd(500, 0.0));
    }

    #[Test]
    public function a_null_rate_passes_the_amount_through(): void
    {
        // A vendor whose currency row is missing yields null, not zero.
        $this->assertSame(500.0, Money::fromUsd(500, null));
        $this->assertSame(500.0, Money::toUsd(500, null));
    }

    #[Test]
    public function a_negative_rate_is_refused_as_unusable(): void
    {
        $this->assertFalse(Money::usable(-1.0));
        $this->assertSame(500.0, Money::fromUsd(500, -1.0));
    }

    #[Test]
    public function converting_between_two_currencies_goes_through_dollars(): void
    {
        // 11,000 CNY is $1,540, which is 12,833,333 GNF.
        $gnf = Money::convert(11000, 0.14, self::GNF);

        $this->assertSame(12833333.0, round($gnf, 0));
    }

    #[Test]
    public function conversion_itself_does_not_round(): void
    {
        // RfqOfferConverter rounded at each step, so a two-step figure was rounded
        // twice. Rounding belongs at the edge, once.
        $this->assertSame(0.000012, Money::toUsd(0.1, self::GNF));
    }

    #[Test]
    public function rounding_follows_the_currency_precision_rather_than_an_assumed_two(): void
    {
        Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => self::GNF, 'precision' => 0]);
        Currency::factory()->create(['code' => 'USD', 'rate_to_usd' => 1, 'precision' => 2]);

        $this->assertSame(1235.0, Money::round(1234.567, 'GNF'));
        $this->assertSame(1234.57, Money::round(1234.567, 'USD'));
    }

    #[Test]
    public function an_unknown_currency_falls_back_to_two_decimals(): void
    {
        $this->assertSame(1234.57, Money::round(1234.567, 'NOPE'));
        $this->assertSame(1234.57, Money::round(1234.567));
    }

    #[Test]
    public function the_homepage_shows_a_real_price_when_the_shopper_switches_currency(): void
    {
        // HomePage::convertPrice multiplied by the selected currency's rate_to_usd
        // where it had to divide, so it converted *into* dollars twice. A $100
        // product shown to someone who picked GNF came out at 0.012 GNF instead
        // of 833,333 — every price on the page collapsing to nothing.
        //
        // It hid because the default currency is USD at a rate of 1, and
        // multiplying by 1 looks identical to dividing by 1. Only the navbar's
        // currency switcher reveals it, which is why the page looks correct until
        // a customer actually uses it.
        Currency::factory()->create(['code' => 'GNF', 'rate_to_usd' => self::GNF, 'precision' => 2]);
        $usd = Currency::factory()->create(['code' => 'USD', 'rate_to_usd' => 1, 'precision' => 2]);

        $vendor = \App\Models\Vendor::factory()->create(['is_active' => true, 'currency_id' => $usd->id]);
        $product = \App\Models\Product::create([
            'category_id' => \App\Models\Category::create(['name' => 'C' . uniqid(), 'slug' => 'c-' . uniqid(), 'is_active' => true])->id,
            'brand_id' => \App\Models\Brand::create(['name' => 'B' . uniqid(), 'slug' => 'b-' . uniqid()])->id,
            'name' => 'Hundred Dollar Thing',
            'slug' => 'hundred-' . uniqid(),
            'is_active' => true,
            'is_featured' => true,
        ]);
        \App\Models\VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'price' => 100,
            'stock' => 5,
            'is_active' => true,
        ]);

        $featured = \Livewire\Livewire::test(\App\Livewire\HomePage::class)
            ->call('updateCurrency', 'GNF')
            ->viewData('featuredProducts');

        $price = (float) $featured->firstWhere('id', $product->id)->display_price;

        // 100 USD at 0.00012 to the dollar is 833,333 GNF. The old code produced
        // 0.012, so anything under a thousand means the inversion is back.
        $this->assertEqualsWithDelta(833333.33, $price, 1.0);
    }
}
