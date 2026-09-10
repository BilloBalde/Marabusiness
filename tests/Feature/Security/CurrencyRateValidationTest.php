<?php

namespace Tests\Feature\Security;

use App\Filament\Resources\CurrencyResource\Pages\CreateCurrency;
use App\Filament\Resources\CurrencyResource\Pages\EditCurrency;
use App\Models\Currency;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * rate_to_usd is a divisor in nine places across the application — RfqOfferConverter,
 * FinanceCalculator, PaymentController, SuccessPageStripe on the server, and
 * checkout_provider.dart in the mobile app. Exactly one of them checks it first
 * (LocalityShippingCalculator:57, `$rateToUsd > 0 ? ... : ...`), which is evidence
 * someone already met this in production and patched it where it hurt.
 *
 * The admin form accepted any number: ->numeric()->default(1), no required, no
 * minimum. Saving 0 there is fatal, not cosmetic — PHP 8 throws DivisionByZeroError
 * on x/0, so an order in that currency 500s instead of taking a payment.
 *
 * Fixing it at the form closes all nine at once. There is one write path for a
 * currency (this admin resource — no vendor panel, no API, no seeder), so the door
 * really does close here.
 */
class CurrencyRateValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create());
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Currency',
            'code' => 'TST',
            'symbol' => 'T',
            'precision' => 2,
            'rate_to_usd' => 1,
            'is_active' => true,
        ], $overrides);
    }

    #[Test]
    public function a_zero_conversion_rate_is_rejected(): void
    {
        Livewire::test(CreateCurrency::class)
            ->fillForm($this->payload(['rate_to_usd' => 0]))
            ->call('create')
            ->assertHasFormErrors(['rate_to_usd']);

        $this->assertDatabaseMissing('currencies', ['code' => 'TST']);
    }

    #[Test]
    public function a_negative_conversion_rate_is_rejected(): void
    {
        Livewire::test(CreateCurrency::class)
            ->fillForm($this->payload(['rate_to_usd' => -0.5]))
            ->call('create')
            ->assertHasFormErrors(['rate_to_usd']);

        $this->assertDatabaseMissing('currencies', ['code' => 'TST']);
    }

    #[Test]
    public function an_empty_conversion_rate_is_rejected(): void
    {
        Livewire::test(CreateCurrency::class)
            ->fillForm($this->payload(['rate_to_usd' => null]))
            ->call('create')
            ->assertHasFormErrors(['rate_to_usd']);

        $this->assertDatabaseMissing('currencies', ['code' => 'TST']);
    }

    #[Test]
    public function a_realistic_rate_still_saves(): void
    {
        // GNF sits at 0.00012, so the minimum has to stay well below any real
        // currency — a naive min:1 or min:0.01 would lock out the marketplace's
        // primary currency.
        Livewire::test(CreateCurrency::class)
            ->fillForm($this->payload(['code' => 'GNF', 'rate_to_usd' => 0.00012]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('currencies', ['code' => 'GNF']);
    }

    #[Test]
    public function an_existing_currency_cannot_be_edited_down_to_zero(): void
    {
        // Editing is the likelier route to a zero rate than creating: someone
        // clears the field to retype a rate, saves, and every order in that
        // currency starts throwing.
        $currency = Currency::factory()->create(['code' => 'EUR', 'rate_to_usd' => 1.1]);

        Livewire::test(EditCurrency::class, ['record' => $currency->getRouteKey()])
            ->fillForm(['rate_to_usd' => 0])
            ->call('save')
            ->assertHasFormErrors(['rate_to_usd']);

        $this->assertSame(1.1, (float) $currency->fresh()->rate_to_usd);
    }
}
