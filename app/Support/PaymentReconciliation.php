<?php

namespace App\Support;

/**
 * Decides whether an order is paid, partial, or pending once a payment has been
 * recorded, tolerating the cent-level rounding that Stripe's payment gateway forces.
 *
 * Stripe only accepts integer USD cents. A vendor-currency line item is converted to
 * USD, rounded to the nearest cent, and that rounded figure is what Stripe actually
 * charges. Converting the charged amount back to the vendor's currency on return
 * essentially never reproduces the order's exact local total for currencies with a
 * small rate_to_usd (GNF ≈ 0.00012, CNY ≈ 0.14): a residual of a fraction of a local
 * unit remains, and comparing it to zero with exact equality left orders permanently
 * marked "partial" — sometimes indefinitely — even though Stripe had charged every
 * cent it was capable of charging for that order.
 *
 * The tolerance is expressed in USD because that is the unit Stripe actually rounds:
 * a residual under one cent's worth, once converted to USD, is not something Stripe
 * itself could have collected, so it does not count as still owed.
 */
class PaymentReconciliation
{
    private const NEGLIGIBLE_USD = 0.01;

    /**
     * @return array{total_paid: float, total_remaining: float, payment_status: string}
     */
    public static function resolve(float $grandTotal, float $totalPaid, ?float $rateToUsd): array
    {
        $rate = ($rateToUsd !== null && $rateToUsd > 0) ? $rateToUsd : 1.0;

        $remaining = max(0.0, $grandTotal - $totalPaid);
        $remainingUsd = $remaining * $rate;

        if ($remainingUsd < self::NEGLIGIBLE_USD) {
            // Snap to the quoted total instead of leaving the rounding dust in
            // total_paid, so the order's own ledger reads as exactly settled.
            return [
                'total_paid' => $grandTotal,
                'total_remaining' => 0.0,
                'payment_status' => 'paid',
            ];
        }

        return [
            'total_paid' => $totalPaid,
            'total_remaining' => $remaining,
            'payment_status' => $totalPaid > 0 ? 'partial' : 'pending',
        ];
    }
}
