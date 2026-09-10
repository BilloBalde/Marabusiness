<?php

namespace App\Support;

use App\Models\Currency;

/**
 * The one place currency conversion happens.
 *
 * Conversion was written out by hand at roughly twenty sites and no two agreed.
 * Some guarded the rate before dividing by it — LocalityShippingCalculator:57 and
 * ShippingCalculator:43 both wrote `$rate > 0 ? ... : $amount` — while
 * CheckoutPage, CheckoutController, RfqOfferConverter, SuccessPage and
 * SuccessPageStripe divided straight through, which is a fatal
 * DivisionByZeroError in PHP 8 the moment a currency carries a zero rate. Some
 * rounded to two decimals mid-calculation, some rounded at the end, most not at
 * all — and the currencies table has carried a `precision` column the whole time
 * that only one screen ever read.
 *
 * The rules, in one place:
 *
 *   - `rate_to_usd` is what one unit of the currency is worth in USD, so a local
 *     amount MULTIPLIES into USD and a USD amount DIVIDES back out.
 *   - A rate that is null, zero or negative cannot convert anything. Rather than
 *     throw in the middle of a checkout, the amount passes through unchanged —
 *     the behaviour the two already-guarded sites chose, and the one that keeps
 *     an order payable instead of returning a 500.
 *   - Conversion does not round. Rounding belongs at the edge, once, via
 *     round(), so a figure converted through two steps is not rounded twice.
 */
final class Money
{
    /**
     * Local currency amount to USD.
     */
    public static function toUsd(float $amount, ?float $rateToUsd): float
    {
        return self::usable($rateToUsd) ? $amount * $rateToUsd : $amount;
    }

    /**
     * USD to a local currency amount.
     */
    public static function fromUsd(float $usdAmount, ?float $rateToUsd): float
    {
        return self::usable($rateToUsd) ? $usdAmount / $rateToUsd : $usdAmount;
    }

    /**
     * Between two currencies, via USD.
     *
     * Used where a figure priced in a vendor's currency has to be shown in the
     * currency the shopper picked.
     */
    public static function convert(float $amount, ?float $fromRateToUsd, ?float $toRateToUsd): float
    {
        return self::fromUsd(self::toUsd($amount, $fromRateToUsd), $toRateToUsd);
    }

    /**
     * Rounds to the currency's own precision rather than an assumed two decimals.
     *
     * Every site hard-coded round($x, 2). GNF has no minor unit in practice, and a
     * currency configured with a different precision was ignored everywhere except
     * one chat screen.
     */
    public static function round(float $amount, Currency|string|int|null $currency = null): float
    {
        return round($amount, self::precisionFor($currency));
    }

    /**
     * Whether a rate can actually convert. Null, zero and negative rates cannot:
     * dividing by them throws, and multiplying by them destroys the amount.
     */
    public static function usable(?float $rateToUsd): bool
    {
        return $rateToUsd !== null && $rateToUsd > 0;
    }

    private static function precisionFor(Currency|string|int|null $currency): int
    {
        if ($currency instanceof Currency) {
            return (int) ($currency->precision ?? 2);
        }

        if (is_int($currency)) {
            return $currency;
        }

        if (is_string($currency) && $currency !== '') {
            return (int) (Currency::where('code', $currency)->value('precision') ?? 2);
        }

        return 2;
    }
}
