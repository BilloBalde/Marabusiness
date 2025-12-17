<?php

namespace App\Helpers;

use App\Models\Product;

class ShippingCalculator
{
    /**
     * Calculate shipping cost for cart items
     */
    public static function calculateCartShipping($cartItems, $destinationCountry, $shippingMethod = 'standard')
    {
        $totalWeight = 0;
        $totalCBM = 0;
        $totalItems = 0;
        
        foreach ($cartItems as $item) {
            // Get product from vendor_product_id
            $product = Product::find($item['product_id']);
            
            if ($product) {
                $totalWeight += $product->getShippingWeight() * $item['quantity'];
                $totalCBM += ($product->cbm ?? 0) * $item['quantity'];
                $totalItems += $item['quantity'];
            }
        }
        
        // Calculate based on weight or CBM (whichever gives higher cost)
        $weightCost = self::calculateByWeight($totalWeight, $destinationCountry, $shippingMethod);
        $cbmCost = self::calculateByCBM($totalCBM, $destinationCountry, $shippingMethod);
        
        return max($weightCost, $cbmCost);
    }
    
    /**
     * Calculate shipping by weight
     */
    private static function calculateByWeight($weight, $country, $method)
    {
        // Base rates per kg (you can make this dynamic from database)
        $rates = [
            'standard' => [
                'local' => 2.5,    // $2.5 per kg locally
                'international' => 8.5, // $8.5 per kg internationally
            ],
            'express' => [
                'local' => 5.0,
                'international' => 15.0,
            ],
            'freight' => [
                'local' => 1.5,
                'international' => 6.0,
            ]
        ];
        
        // Determine if local or international
        $isInternational = !in_array($country, ['GN', 'ML', 'SN', 'CI']); // Example local countries
        
        $rate = $rates[$method][$isInternational ? 'international' : 'local'];
        
        // Minimum charge
        $minimum = $isInternational ? 25 : 10;
        
        return max($minimum, $weight * $rate);
    }
    
    /**
     * Calculate shipping by CBM
     */
    private static function calculateByCBM($cbm, $country, $method)
    {
        // Base rates per CBM
        $rates = [
            'standard' => [
                'local' => 150,    // $150 per CBM locally
                'international' => 450, // $450 per CBM internationally
            ],
            'express' => [
                'local' => 300,
                'international' => 750,
            ],
            'freight' => [
                'local' => 100,
                'international' => 350,
            ]
        ];
        
        $isInternational = !in_array($country, ['GN', 'ML', 'SN', 'CI']);
        $rate = $rates[$method][$isInternational ? 'international' : 'local'];
        
        $minimum = $isInternational ? 100 : 50;
        
        return max($minimum, $cbm * $rate);
    }
    
    /**
     * Get available shipping methods
     */
    public static function getShippingMethods($weight, $cbm, $country)
    {
        $isInternational = !in_array($country, ['GN', 'ML', 'SN', 'CI']);
        
        return [
            'standard' => [
                'name' => 'Standard Shipping',
                'cost' => self::calculateByWeight($weight, $country, 'standard'),
                'eta' => $isInternational ? '15-25 days' : '3-7 days',
                'description' => 'Economical shipping with tracking',
            ],
            'express' => [
                'name' => 'Express Shipping',
                'cost' => self::calculateByWeight($weight, $country, 'express'),
                'eta' => $isInternational ? '5-10 days' : '1-3 days',
                'description' => 'Fast delivery with priority handling',
            ],
            'freight' => [
                'name' => 'Freight Shipping',
                'cost' => self::calculateByWeight($weight, $country, 'freight'),
                'eta' => $isInternational ? '20-40 days' : '7-14 days',
                'description' => 'Bulk shipment for large orders',
            ],
        ];
    }
}