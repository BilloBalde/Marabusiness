<?php

namespace App\Services\Shipping;

use App\Models\Vendor;
use App\Services\ShippingCalculator;

/**
 * Single entry point for shipping quotes. Routes each vendor of a cart to the
 * calculator its `shipping_mode` selects, then merges the results into the one array
 * shape the checkout, the API and the POS already read.
 *
 * The legacy carrier calculator is used untouched, and is only invoked when the cart
 * actually contains a carrier-mode vendor — which also spares a pure-locality cart the
 * rows that calculator inserts while quoting.
 */
class CartShippingResolver
{
    public function __construct(
        private ?ShippingCalculator $carrierCalculator = null,
        private ?LocalityShippingCalculator $localityCalculator = null,
    ) {
        $this->carrierCalculator  ??= new ShippingCalculator();
        $this->localityCalculator ??= new LocalityShippingCalculator();
    }

    public function calculateCartShipping(
        array $groupedCartItems,
        array $destinationAddress,
        ?string $preferredCarrier = null
    ): array {
        [$carrierItems, $localityItems, $localityVendors] = $this->splitByMode($groupedCartItems);

        // Nothing to merge: hand back the untouched result of the relevant calculator.
        if (empty($localityItems)) {
            return $this->carrierCalculator->calculateCartShipping(
                $carrierItems, $destinationAddress, $preferredCarrier
            );
        }

        if (empty($carrierItems)) {
            return $this->localityCalculator->calculateCartShipping(
                $localityItems, $destinationAddress, $preferredCarrier
            );
        }

        return $this->merge(
            $this->carrierCalculator->calculateCartShipping($carrierItems, $destinationAddress, $preferredCarrier),
            $localityItems,
            $localityVendors,
            $destinationAddress,
            $preferredCarrier
        );
    }

    /**
     * Quote one vendor, whichever mode it is on.
     */
    public function calculateVendorShipping(
        Vendor $vendor,
        array $cartItems,
        array $destinationAddress,
        ?string $preferredCarrier = null
    ): array {
        return $vendor->usesLocalityShipping()
            ? $this->localityCalculator->calculateVendorShipping($vendor, $cartItems, $destinationAddress, $preferredCarrier)
            : $this->carrierCalculator->calculateVendorShipping($vendor, $cartItems, $destinationAddress, $preferredCarrier);
    }

    /**
     * @return array{0: array<int, mixed>, 1: array<int, mixed>, 2: array<int, Vendor>}
     */
    private function splitByMode(array $groupedCartItems): array
    {
        $carrierItems    = [];
        $localityItems   = [];
        $localityVendors = [];

        $vendors = Vendor::with('currency')
            ->whereIn('id', array_keys($groupedCartItems))
            ->get()
            ->keyBy('id');

        foreach ($groupedCartItems as $vendorId => $items) {
            $vendor = $vendors->get($vendorId);

            // An unknown vendor is left on the carrier path, which already skips it.
            if ($vendor && $vendor->usesLocalityShipping()) {
                $localityItems[$vendorId]   = $items;
                $localityVendors[$vendorId] = $vendor;
                continue;
            }

            $carrierItems[$vendorId] = $items;
        }

        return [$carrierItems, $localityItems, $localityVendors];
    }

    /**
     * A locality vendor charges the same amount whatever carrier the buyer picks for
     * the rest of the cart, so its cost is added to every carrier bucket. Adding it to
     * only one would make its delivery fee vanish as soon as the buyer switched
     * carrier, and under-charge the order.
     */
    private function merge(
        array $carrierResult,
        array $localityItems,
        array $localityVendors,
        array $destinationAddress,
        ?string $preferredCarrier
    ): array {
        $localityOptions = [];
        $localityTotal   = 0.0;

        foreach ($localityItems as $vendorId => $items) {
            $result = $this->localityCalculator->calculateVendorShipping(
                $localityVendors[$vendorId], $items, $destinationAddress, $preferredCarrier
            );

            $carrierResult['vendor_breakdown'][$vendorId] = $result;
            $localityOptions[$vendorId] = $result['selected'];
            $localityTotal += $result['selected']['cost'];
        }

        // If the carrier side produced nothing usable, fall back to a single bucket so
        // the checkout still has one selectable option.
        if (empty($carrierResult['carriers'])) {
            $carrierResult['carriers'][LocalityShippingCalculator::CARRIER_KEY] = [
                'name'           => reset($localityOptions)['name'],
                'carrier'        => LocalityShippingCalculator::CARRIER_KEY,
                'total_cost_usd' => 0.0,
                'vendors'        => [],
                'is_available'   => true,
            ];
        }

        foreach ($carrierResult['carriers'] as $key => $carrier) {
            foreach ($localityOptions as $vendorId => $option) {
                $carrierResult['carriers'][$key]['vendors'][$vendorId] = $option;
                $carrierResult['carriers'][$key]['total_cost_usd'] += $option['cost'];
            }
        }

        uasort(
            $carrierResult['carriers'],
            fn ($a, $b) => $a['total_cost_usd'] <=> $b['total_cost_usd']
        );

        $carrierResult['total_shipping_usd'] = round(
            ($carrierResult['total_shipping_usd'] ?? 0) + $localityTotal,
            2
        );

        return $carrierResult;
    }
}
