<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Services\Shipping\CartShippingResolver;
use App\Support\ShippingCarrierFilter;
use Illuminate\Support\Facades\Log;

/**
 * Le calcul de livraison du checkout, partagé.
 *
 * CheckoutController le portait en privé. Ouvrir une négociation crée une vraie
 * commande, avec de vrais frais de port, et la seule autre façon de les obtenir
 * aurait été de laisser le téléphone les envoyer — c'est-à-dire de laisser le
 * client fixer un montant qui finira sur une facture. Les deux contrôleurs
 * appellent donc le même code, comme OrderNegotiation est déjà partagé entre le
 * web et l'API.
 */
trait CalculatesCheckoutShipping
{
    private ?CartShippingResolver $sharedShippingCalculator = null;

    protected function shippingCalculator(): CartShippingResolver
    {
        return $this->sharedShippingCalculator ??= new CartShippingResolver();
    }

    protected function calculateFullShipping($request, $selectedItems)
    {
        // Group items by vendor with full details
        $groupedItems = [];
        foreach ($selectedItems as $item) {
            $vendorId = $item['vendor_id'];
            if (!isset($groupedItems[$vendorId])) {
                $groupedItems[$vendorId] = [];
            }
            $groupedItems[$vendorId][] = [
                'product_id' => $item['product_id'] ?? null,
                'vendor_product_id' => $item['vendor_product_id'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'weight' => $item['weight'] ?? 0,
                'length' => $item['length'] ?? 0,
                'width' => $item['width'] ?? 0,
                'height' => $item['height'] ?? 0,
            ];
        }

        $destinationAddress = [
            'first_name' => $request->address['first_name'],
            'last_name' => $request->address['last_name'],
            'city' => $request->address['city'],
            'state' => $request->address['state'],
            'zip_code' => $request->address['zip_code'] ?? null,
            'locality_id' => $request->address['locality_id'] ?? null,
            'country' => $request->address['country'],
            'country_code' => $this->getCountryCode($request->address['country']),
            'street_address' => $request->address['street_address'],
            'latitude' => $request->address['latitude'] ?? null,
            'longitude' => $request->address['longitude'] ?? null,
        ];

        $shippingResult = $this->shippingCalculator()->calculateCartShipping(
            $groupedItems,
            $destinationAddress,
            $request->shipping_carrier
        );

        // Only "Local Courier" is ever charged, regardless of what shipping_carrier the
        // request asked for — otherwise a client could still be billed a DHL/CMA-tier
        // price by naming it explicitly, even with those options hidden from the UI.
        $shippingResult['carriers'] = ShippingCarrierFilter::onlyLocal($shippingResult['carriers'] ?? []);

        // Get the selected carrier data from the carriers array
        $selectedCarrierKey = $request->shipping_carrier;
        $carrierData = $shippingResult['carriers'][$selectedCarrierKey] ?? null;

        if (!$carrierData && !empty($shippingResult['carriers'])) {
            // Fallback to first carrier if selected not found
            $firstCarrierKey = array_key_first($shippingResult['carriers']);
            $carrierData = $shippingResult['carriers'][$firstCarrierKey];
            $selectedCarrierKey = $firstCarrierKey;
        }

        // Format the result with vendor details from the carrier data
        $formattedResult = [
            'name' => $carrierData['name'] ?? 'Local Delivery',
            'carrier' => $selectedCarrierKey,
            'total_cost_usd' => $carrierData['total_cost_usd'] ?? 0,
            'vendors' => [],
        ];

        // Vendors come from the carrier data, not from the root of the result
        foreach ($carrierData['vendors'] ?? [] as $vendorId => $vendorData) {
            $vendorItems = $groupedItems[$vendorId] ?? [];
            $totalWeight = 0;
            $totalCbm = 0;
            $totalItems = 0;

            foreach ($vendorItems as $item) {
                $quantity = $item['quantity'];
                $totalItems += $quantity;
                $totalWeight += ($item['weight'] ?? 0) * $quantity;

                $length = $item['length'] ?? 0;
                $width = $item['width'] ?? 0;
                $height = $item['height'] ?? 0;
                $cbmPerItem = ($length * $width * $height) / 1000000;
                $totalCbm += $cbmPerItem * $quantity;
            }

            $formattedResult['vendors'][$vendorId] = [
                'cost' => $vendorData['cost'] ?? 0,
                'zone' => $vendorData['zone'] ?? 'Unknown',
                'delivery_days' => $vendorData['delivery_days'] ?? 3,
                'total_weight' => $totalWeight,
                'total_cbm' => $totalCbm,
                'total_items' => $totalItems,
            ];
        }

        Log::info('Formatted shipping result:', [
            'selected_carrier' => $selectedCarrierKey,
            'vendors' => array_keys($formattedResult['vendors']),
            'vendor_data' => $formattedResult['vendors'],
        ]);

        return $formattedResult;
    }

    protected function getCountryCode($country)
    {
        $countryCodes = [
            'Guinea' => 'GN',
            'Senegal' => 'SN',
            'Ivory Coast' => 'CI',
            'Mali' => 'ML',
            'France' => 'FR',
            'United States' => 'US',
            'Canada' => 'CA',
            'United Kingdom' => 'GB',
        ];

        return $countryCodes[$country] ?? strtoupper(substr($country, 0, 2));
    }
}
