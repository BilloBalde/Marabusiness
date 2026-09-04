<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\ShippingCarrierRate;
use App\Models\Shipment;

class ShippingCalculator
{
    /**
     * Calculate shipping cost for a vendor's items
     */
    public function calculateVendorShipping(
            Vendor $vendor,
            array $cartItems,
            array $destinationAddress,
            string $preferredCarrier = null
        ): array {
        $zone = $this->determineShippingZone($vendor, $destinationAddress);

        // IMPORTANT: your CheckoutPage passes plain items array per vendor
        $items = $cartItems['items'] ?? $cartItems;

        $totals = $this->calculateTotals($items);

        $carriers = $this->getAvailableCarriers($vendor, $zone);

        $options = [];

        foreach ($carriers as $carrier) {
            $costLocal = $this->calculateCarrierCostLocal(
                $carrier,
                $totals,
                $cartItems['subtotal'] ?? null // optional if you ever pass subtotal
            );

            $rateToUsd = (float) ($vendor?->currency?->rate_to_usd ?? $vendor->rate_to_usd ?? 1);

            // Local -> USD (if rate invalid, fallback 1)
            $costUsd = ($rateToUsd > 0) ? ($costLocal * $rateToUsd) : $costLocal;

            $options[] = [
                'carrier'        => $carrier->carrier,
                'name'           => Shipment::CARRIERS[$carrier->carrier] ?? $carrier->carrier,
                'cost'           => $costUsd,        // USD (used by checkout)
                'cost_local'     => $costLocal,      // vendor currency
                'delivery_days'  => (int) ($carrier->delivery_days ?? 5),
                'zone'           => $zone->name,
                'total_weight'   => $totals['weight'],
                'total_cbm'      => $totals['cbm'],
                'total_items'    => $totals['items'],
                'total_cartons'  => $totals['cartons'],
            ];
        }

        // Sort by USD cost
        usort($options, fn ($a, $b) => $a['cost'] <=> $b['cost']);

        $selectedOption = $options[0] ?? null;

        if ($preferredCarrier) {
            foreach ($options as $option) {
                if ($option['carrier'] === $preferredCarrier) {
                    $selectedOption = $option;
                    break;
                }
            }
        }

        return [
            'options'  => $options,
            'selected' => $selectedOption,
            'zone'     => $zone,
            'totals'   => $totals,
        ];
    }

    /**
     * Calculate carrier cost in vendor's local currency
     */
    private function calculateCarrierCostLocal(
            ShippingCarrierRate $carrier,
            array $totals,
            ?float $orderSubtotalLocal = null
        ): float {
        // Free shipping check (if subtotal provided)
        if (
            $orderSubtotalLocal !== null
            && (float) $carrier->free_shipping_threshold > 0
            && $orderSubtotalLocal >= (float) $carrier->free_shipping_threshold
        ) {
            return 0.0;
        }

        $cost =
            (float) $carrier->base_rate
            + ((float) $carrier->rate_per_kg     * (float) $totals['weight'])
            + ((float) $carrier->rate_per_cbm    * (float) $totals['cbm'])
            + ((float) $carrier->rate_per_item   * (int) $totals['items'])
            + ((float) $carrier->rate_per_carton * (int) $totals['cartons']);

        // Apply min/max from DB
        if ((float) $carrier->min_rate > 0 && $cost < (float) $carrier->min_rate) {
            $cost = (float) $carrier->min_rate;
        }

        if ((float) $carrier->max_rate > 0 && $cost > (float) $carrier->max_rate) {
            $cost = (float) $carrier->max_rate;
        }

        return round($cost, 2);
    }

    
    /**
     * Determine shipping zone based on destination
     */
    private function determineShippingZone(Vendor $vendor, array $address): ShippingZone
    {
        // Check if vendor has custom zones
        $customZone = $this->findCustomZone($vendor, $address);
        if ($customZone) {
            return $customZone;
        }
        
        // Calculate distance from vendor
        $distance = $this->calculateDistance(
            $vendor->latitude, $vendor->longitude,
            $address['latitude'] ?? null, $address['longitude'] ?? null
        );
        
        // Determine zone based on distance
        if ($distance <= 50) { // Within 50km
            return $this->getOrCreateZone($vendor, 'Zone A', 50, 5, 2, 0.5);
        } elseif ($distance <= 300) { // Within 300km
            return $this->getOrCreateZone($vendor, 'Zone B', 300, 15, 5, 2);
        } elseif ($address['country'] === $vendor->country) {
            return $this->getOrCreateZone($vendor, 'Zone C', 1000, 25, 10, 5);
        } else {
            return $this->getOrCreateZone($vendor, 'Zone International', null, 50, 20, 15);
        }
    }
    
    /**
     * Find custom zone matching the address
     */
    private function findCustomZone(Vendor $vendor, array $address): ?ShippingZone
    {
        $countryCode = $address['country_code'] ?? null;

        if ($countryCode) {
            $zone = ShippingZone::where('vendor_id', $vendor->id)
                ->where('country_code', $countryCode)
                ->where('is_active', true)
                ->first();

            if ($zone) return $zone;
        }

        if (!empty($address['city'])) {
            $zone = ShippingZone::where('vendor_id', $vendor->id)
                ->where('is_active', true)
                ->where(function($query) use ($address) {
                    $query->where('cities', 'LIKE', "%{$address['city']}%")
                        ->orWhere('region', $address['state'] ?? '');
                })
                ->first();

            if ($zone) return $zone;
        }

        return null;
    }
    
    /**
     * Get or create a default zone
     */
    private function getOrCreateZone(
        Vendor $vendor, 
        string $name, 
        ?int $radius,
        float $basePrice,
        float $perKg,
        float $perCbm
    ): ShippingZone
    {
        $zone = ShippingZone::firstOrCreate(
            [
                'vendor_id' => $vendor->id,
                'name' => $name,
            ],
            [
                'radius_km' => $radius,
                'base_price' => $basePrice,
                'price_per_kg' => $perKg,
                'price_per_cbm' => $perCbm,
                'price_per_item' => 0,
                'min_days' => 1,
                'max_days' => $radius ? ceil($radius / 100) + 2 : 14,
                'is_active' => true,
            ]
        );
        
        // Also ensure default carrier rates exist
        $this->ensureDefaultCarrierRates($vendor, $zone);
        
        return $zone;
    }
    
    /**
     * Ensure default carrier rates exist for a zone
     */
    private function ensureDefaultCarrierRates(Vendor $vendor, ShippingZone $zone): void
    {
        $defaultCarriers = ['local', 'chrono', 'dhl', 'ups', 'fedex'];
        
        foreach ($defaultCarriers as $carrier) {
            ShippingCarrierRate::firstOrCreate(
                [
                    'vendor_id' => $vendor->id,
                    'carrier' => $carrier,
                    'zone_name' => $zone->name,
                ],
                [
                    'base_rate' => $zone->base_price * $this->getCarrierMultiplier($carrier),
                    'rate_per_kg' => $zone->price_per_kg * $this->getCarrierMultiplier($carrier),
                    'rate_per_cbm' => $zone->price_per_cbm * $this->getCarrierMultiplier($carrier),
                    'rate_per_item' => 0,
                    'delivery_days' => $this->getCarrierDeliveryDays($carrier, $zone),
                    'is_active' => true,
                ]
            );
        }
    }
    
    /**
     * Get carrier multiplier for pricing
     */
    private function getCarrierMultiplier(string $carrier): float
    {
        return match($carrier) {
            'local' => 1.0,
            'chrono' => 1.3,
            'dhl', 'ups', 'fedex' => 2.0,
            default => 1.2,
        };
    }
    
    /**
     * Get carrier delivery days
     */
    private function getCarrierDeliveryDays(string $carrier, ShippingZone $zone): int
    {
        $baseDays = ceil(($zone->min_days + $zone->max_days) / 2);
        
        return match($carrier) {
            'chrono' => max(1, $baseDays - 2),
            'dhl', 'ups', 'fedex' => max(3, $baseDays - 1),
            default => $baseDays,
        };
    }
    
    /**
     * Calculate totals from cart items
     */
    private function calculateTotals(array $cartItems): array
    {
        \Log::info('calculateTotals received items:', $cartItems); // ADD THIS

        $totalWeight = 0;
        $totalCBM = 0;
        $totalItems = 0;
        $totalCartons = 0;
        $totalQuantity = 0;
        
        foreach ($cartItems as $item) {
            // Check if product_id exists
            if (!isset($item['product_id'])) {
                \Log::error('Missing product_id in item:', $item);
                continue;
            }
            
            $product = Product::find($item['product_id']);
            
            if (!$product) {
                \Log::error('Product not found:', ['product_id' => $item['product_id']]);
                continue;
            }
            //dd($product);
            
            if ($product) {
                $quantity = $item['quantity'] ?? 1;
                $totalItems++;
                $totalQuantity += $quantity;
                
                // Calculate based on carton if available
                if ($product->qty_per_carton && $product->qty_per_carton > 0) {
                    $cartonsNeeded = ceil($quantity / $product->qty_per_carton);
                    $totalCartons += $cartonsNeeded;
                    
                    if ($product->carton_weight) {
                        $totalWeight += $product->carton_weight * $cartonsNeeded;
                    }
                    if ($product->carton_cbm) {
                        $totalCBM += $product->carton_cbm * $cartonsNeeded;
                    }
                } else {
                    // Fallback to unit calculations
                    $totalWeight += ($product->weight ?? 0.1) * $quantity; // Default 0.1kg per item
                    $totalCBM += ($product->cbm ?? 0.001) * $quantity; // Default 0.001 CBM per item
                }
            }
        }
        
        return [
            'weight' => max(0.1, $totalWeight), // Minimum 0.1kg
            'cbm' => max(0.001, $totalCBM), // Minimum 0.001 CBM
            'items' => $totalItems,
            'cartons' => $totalCartons,
            'quantity' => $totalQuantity,
        ];
    }
    
    /**
     * Get available carriers for a zone
     */
    private function getAvailableCarriers(Vendor $vendor, ShippingZone $zone)
    {
        return ShippingCarrierRate::query()
            ->where('vendor_id', $vendor->id)
            ->where('zone_name', $zone->name)
            ->where('is_active', true)
            ->orderByDesc('is_default') // default first
            ->orderBy('base_rate')      // then cheapest-ish
            ->get();
    }

    
    /**
     * Calculate carrier cost
     */
    private function calculateCarrierCost(array $carrier, array $totals): float
    {
        $cost = $carrier['base_rate'];
        $cost += $carrier['rate_per_kg'] * $totals['weight'];
        $cost += $carrier['rate_per_cbm'] * $totals['cbm'];
        $cost += $carrier['rate_per_item'] * $totals['items'];
        
        // Apply minimum charge
        $minimum = $carrier['zone_name'] === 'Zone International' ? 50 : 10;
        
        return max($minimum, $cost);
    }
    
    /**
     * Calculate distance between two coordinates (in km)
     */
    private function calculateDistance(
        ?float $lat1, 
        ?float $lon1, 
        ?float $lat2, 
        ?float $lon2
    ): float
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
            return 1000; // Default to far distance if coordinates missing
        }
        
        $earthRadius = 6371; // Earth's radius in km
        
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $angle * $earthRadius;
    }
    
    /**
     * Calculate shipping for entire cart (multiple vendors)
     */
    public function calculateCartShipping(
        array $groupedCartItems, // Grouped by vendor_id
        array $destinationAddress,
        string $preferredCarrier = null
    ): array
    {
        $vendorShipping = [];
        $totalShippingUSD = 0;
        $allCarriers = [];
        
        foreach ($groupedCartItems as $vendorId => $items) {
            $vendor = Vendor::find($vendorId);
            
            if ($vendor) {
                //dd($items);
                $vendorResult = $this->calculateVendorShipping(
                    $vendor, 
                    $items, 
                    $destinationAddress,
                    $preferredCarrier
                );
                
                $vendorShipping[$vendorId] = $vendorResult;
                $totalShippingUSD += $vendorResult['selected']['cost'] ?? 0;
                
                // Collect all carrier options
                foreach ($vendorResult['options'] ?? [] as $option) {
                    $carrierKey = $option['carrier'];
                    if (!isset($allCarriers[$carrierKey])) {
                        $allCarriers[$carrierKey] = [
                            'name' => $option['name'],
                            'carrier' => $carrierKey,
                            'total_cost_usd' => 0,
                            'vendors' => [],
                            'is_available' => true,
                        ];
                    }
                    $allCarriers[$carrierKey]['total_cost_usd'] += $option['cost'];
                    $allCarriers[$carrierKey]['vendors'][$vendorId] = $option;
                }
            }
        }
        
        // Sort carriers by total cost
        uasort($allCarriers, fn($a, $b) => $a['total_cost_usd'] <=> $b['total_cost_usd']);
        
        return [
            'vendor_breakdown' => $vendorShipping,
            'carriers' => $allCarriers,
            'total_shipping_usd' => $totalShippingUSD,
            'currency' => 'USD',
        ];
    }
    
    /**
     * Get simplified shipping carriers for checkout display
     */
    public function getSimplifiedCarriers(array $cartShippingResult): array
    {
        $simplified = [];
        
        foreach ($cartShippingResult['carriers'] as $carrierKey => $carrier) {
            $simplified[$carrierKey] = [
                'name' => $carrier['name'],
                'price_usd' => $carrier['total_cost_usd'],
                'description' => $this->getCarrierDescription($carrierKey),
                'zones' => array_unique(array_column($carrier['vendors'], 'zone')),
                'vendors' => $carrier['vendors'],
                'is_available' => $carrier['is_available'],
            ];
        }
        
        return $simplified;
    }
    
    private function getCarrierDescription(string $carrier): string
    {
        return match($carrier) {
            'local' => 'Livraison par coursier local - 48h',
            'chrono' => 'Chronopost - Livraison express 24-48h',
            'dhl' => 'DHL Express - Livraison internationale',
            'ups' => 'UPS - Livraison standard',
            'fedex' => 'FedEx - Livraison rapide',
            default => 'Service de livraison',
        };
    }
}