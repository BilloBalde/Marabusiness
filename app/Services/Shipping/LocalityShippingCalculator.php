<?php

namespace App\Services\Shipping;

use App\Models\DeliveryZonePrice;
use App\Models\Locality;
use App\Models\Shipment;
use App\Models\Vendor;

/**
 * Delivery pricing driven by the buyer's locality alone — no carrier, no zone, no
 * weight, nothing for the buyer to choose. A locality's price is shared: whoever set
 * it (admin or any vendor) fixes the one amount every vendor is quoted, in USD, each
 * converting it through their own currency.rate_to_usd. A vendor not yet priced by
 * anyone falls back to its own default_shipping_amount.
 *
 * Returns exactly the array shape of App\Services\ShippingCalculator so the checkout
 * pages, the API and the POS consume it without a single change. Two extra keys,
 * `pricing_mode` and `is_covered`, are additive and let CartShippingResolver merge a
 * locality vendor into a mixed cart.
 *
 * Unlike the carrier calculator this one never writes to the database while quoting.
 */
class LocalityShippingCalculator
{
    /**
     * Locality pricing is not carrier-based, but the checkout UI, orders.shipping_carrier
     * and the Shipment model are all keyed on a carrier. 'local' is the existing key that
     * matches, and an admin can still change it on the order afterwards.
     */
    public const CARRIER_KEY = 'local';

    public const DEFAULT_DELIVERY_DAYS = 2;

    public function calculateVendorShipping(
        Vendor $vendor,
        array $cartItems,
        array $destinationAddress,
        ?string $preferredCarrier = null
    ): array {
        // CheckoutPage and the API pass a plain list of items; the POS passes
        // ['vendor' => ..., 'items' => [...], 'subtotal' => ...]. Same unwrap as the
        // carrier calculator so both shapes keep working.
        $items = $cartItems['items'] ?? $cartItems;

        $totals    = $this->calculateTotals($items);
        $locality  = $this->resolveLocality($destinationAddress);
        $zonePrice = $this->findZonePrice($locality);
        $rateToUsd = (float) ($vendor->currency?->rate_to_usd ?? 1);

        if ($zonePrice) {
            // Shared price: fixed in USD by whoever set it, converted to THIS vendor's
            // currency. Two vendors billing in different currencies see the same USD
            // value, each expressed in their own money — that is what "everyone sees
            // the price for this zone" means once currencies differ.
            $baseCostUsd   = $zonePrice->usdForQuantity($totals['quantity']);
            $baseCostLocal = $rateToUsd > 0 ? $baseCostUsd / $rateToUsd : $baseCostUsd;
        } else {
            // Nobody has priced this zone yet: the vendor's own fallback, which is
            // set in the vendor's own currency, unlike the shared price above.
            $baseCostLocal = (float) ($vendor->default_shipping_amount ?? 0);
            $baseCostUsd   = $baseCostLocal * $rateToUsd;
        }

        // A vendor shipping from outside Guinea adds their own flat freight cost on
        // top of the shared zone price — set once by that vendor, in their own
        // currency, and never compared against any address's country string. That
        // string comparison is exactly what broke the legacy carrier calculator
        // (see ShippingCalculator::determineShippingZone()), so it is deliberately
        // avoided here: a Guinea-based vendor simply leaves this at zero.
        $surchargeLocal = (float) ($vendor->international_shipping_surcharge ?? 0);
        $surchargeUsd   = $surchargeLocal * $rateToUsd;

        $costLocal = $baseCostLocal + $surchargeLocal;
        $costUsd   = $baseCostUsd + $surchargeUsd;

        $option = [
            'carrier'       => self::CARRIER_KEY,
            'name'          => Shipment::CARRIERS[self::CARRIER_KEY] ?? 'Livraison locale',
            'cost'          => round($costUsd, 2),
            'cost_local'    => round($costLocal, 2),
            'delivery_days' => (int) ($zonePrice->delivery_days ?? self::DEFAULT_DELIVERY_DAYS),
            'zone'          => $this->zoneLabel($locality, $destinationAddress),
            // Locality pricing ignores weight and volume; reported as zero rather than
            // invented so the checkout summary does not display a made-up figure.
            'total_weight'  => 0.0,
            'total_cbm'     => 0.0,
            'total_items'   => $totals['items'],
            'total_cartons' => 0,
        ];

        return [
            'options'      => [$option],
            'selected'     => $option,
            'zone'         => $option['zone'],
            'totals'       => $totals,
            'pricing_mode' => Vendor::SHIPPING_MODE_LOCALITY,
            'is_covered'   => $zonePrice !== null,
        ];
    }

    /**
     * Mirrors ShippingCalculator::calculateCartShipping(). In practice the resolver
     * calls calculateVendorShipping() per vendor, but keeping this makes the class a
     * drop-in replacement for a marketplace running entirely on locality pricing.
     */
    public function calculateCartShipping(
        array $groupedCartItems,
        array $destinationAddress,
        ?string $preferredCarrier = null
    ): array {
        $vendorBreakdown  = [];
        $carriers         = [];
        $totalShippingUsd = 0.0;

        foreach ($groupedCartItems as $vendorId => $items) {
            $vendor = Vendor::with('currency')->find($vendorId);

            if (! $vendor) {
                continue;
            }

            $result = $this->calculateVendorShipping($vendor, $items, $destinationAddress, $preferredCarrier);

            $vendorBreakdown[$vendorId] = $result;
            $totalShippingUsd          += $result['selected']['cost'];

            $carriers[self::CARRIER_KEY] ??= [
                'name'           => $result['selected']['name'],
                'carrier'        => self::CARRIER_KEY,
                'total_cost_usd' => 0.0,
                'vendors'        => [],
                'is_available'   => true,
            ];

            $carriers[self::CARRIER_KEY]['total_cost_usd'] += $result['selected']['cost'];
            $carriers[self::CARRIER_KEY]['vendors'][$vendorId] = $result['selected'];
        }

        return [
            'vendor_breakdown'   => $vendorBreakdown,
            'carriers'           => $carriers,
            'total_shipping_usd' => round($totalShippingUsd, 2),
            'currency'           => 'USD',
        ];
    }

    /**
     * The locality the buyer is being delivered to. Prefers the explicit locality_id;
     * falls back to matching the city against locality names so addresses saved before
     * this feature still resolve.
     */
    public function resolveLocality(array $destinationAddress): ?Locality
    {
        if (! empty($destinationAddress['locality_id'])) {
            return Locality::find($destinationAddress['locality_id']);
        }

        $city = trim((string) ($destinationAddress['city'] ?? ''));

        if ($city === '') {
            return null;
        }

        return Locality::selectable()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($city)])
            ->first();
    }

    /**
     * No vendor filter, by design: the price belongs to the zone, not to whoever
     * quotes it.
     */
    private function findZonePrice(?Locality $locality): ?DeliveryZonePrice
    {
        if (! $locality) {
            return null;
        }

        return DeliveryZonePrice::with('tiers')
            ->where('locality_id', $locality->id)
            ->where('is_active', true)
            ->first();
    }

    private function zoneLabel(?Locality $locality, array $destinationAddress): string
    {
        if ($locality) {
            return $locality->name;
        }

        $city = trim((string) ($destinationAddress['city'] ?? ''));

        return $city !== '' ? $city : 'Localité non précisée';
    }

    /**
     * Only line count and total quantity matter here: quantity drives the brackets.
     * No Product lookup, so quoting a cart costs no queries per item.
     */
    private function calculateTotals(array $items): array
    {
        $lineCount = 0;
        $quantity  = 0;

        foreach ($items as $item) {
            $lineCount++;
            $quantity += (int) ($item['quantity'] ?? 1);
        }

        return [
            'weight'   => 0.0,
            'cbm'      => 0.0,
            'items'    => $lineCount,
            'cartons'  => 0,
            'quantity' => $quantity,
        ];
    }
}
