<?php

namespace App\Support;

/**
 * Restricts a shipping quote to "Local Courier" only.
 *
 * DHL, UPS, FedEx, Chronopost and CMA CGM were never a real multi-carrier system —
 * every carrier uses the same price formula with different constants, delivery days
 * are unrealistically short for what are nominally ocean freight lines, and nothing
 * in the codebase distinguishes air from sea freight. `ensureDefaultCarrierRates()`
 * silently created five of them the first time any vendor was quoted, and the
 * checkout pages rendered every active row it found — so a shopper saw a "choice"
 * that carried no real logistics meaning behind it.
 *
 * This filters the result of ShippingCalculator / CartShippingResolver at the point
 * each caller consumes it, leaving that calculation engine itself untouched: existing
 * carrier rows, the admin screens that manage them, and vendors already using the
 * locality-based calculator are all unaffected.
 *
 * Applied at every call site that either quotes a price to the shopper or charges one
 * on the real order — not just the checkout page's carrier list — so a client cannot
 * see or be billed a DHL/CMA-tier price by requesting a different carrier key.
 */
class ShippingCarrierFilter
{
    public const CARRIER_KEY = 'local';

    /**
     * @param array<string, array<string, mixed>> $carriers keyed by carrier code, as
     *        returned in calculateCartShipping()['carriers']
     * @return array<string, array<string, mixed>>
     */
    public static function onlyLocal(array $carriers): array
    {
        if (isset($carriers[self::CARRIER_KEY])) {
            return [self::CARRIER_KEY => $carriers[self::CARRIER_KEY]];
        }

        // 'local' is always the first carrier ensureDefaultCarrierRates() creates, so
        // this only triggers on data that predates that generator or was edited by
        // hand — keep the cheapest rather than leaving checkout with no option at all.
        if ($carriers === []) {
            return [];
        }

        $cheapestKey = array_key_first($carriers);
        $cheapestCost = $carriers[$cheapestKey]['total_cost_usd'] ?? INF;

        foreach ($carriers as $key => $carrier) {
            $cost = $carrier['total_cost_usd'] ?? INF;
            if ($cost < $cheapestCost) {
                $cheapestKey = $key;
                $cheapestCost = $cost;
            }
        }

        return [$cheapestKey => $carriers[$cheapestKey]];
    }
}
