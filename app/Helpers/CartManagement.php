<?php

namespace App\Helpers;

use App\Models\VendorProduct;
use Illuminate\Support\Facades\Cookie;

class CartManagement
{
    /**
     * Get cart items from cookie
     */
    // In CartManagement.php, update getCartItemsFromCookie method:
    static public function getCartItemsFromCookie()
    {
        $cart_items = json_decode(Cookie::get('cart_items') ?? '[]', true);
        $cart_items = is_array($cart_items) ? $cart_items : [];
        
        // Add missing cart_key for backward compatibility
        foreach ($cart_items as &$item) {
            if (!isset($item['cart_key']) && isset($item['vendor_product_id'])) {
                $variation_note = $item['variation_note'] ?? '';
                $item['cart_key'] = self::generateCartKey($item['vendor_product_id'], $variation_note);
            }
        }
        
        return $cart_items;
    }

    /**
     * Save cart items to cookie
     */
    static public function addCartItemsToCookie($cart_items)
    {
        $cookie = cookie('cart_items', json_encode(array_values($cart_items)), 60 * 24 * 30);
        Cookie::queue($cookie);
        return true;
    }

    /**
     * Get vendor product data
     */
    static private function getVendorProductData($vendor_product_id)
    {
        $vendorProduct = VendorProduct::with([
            'product', 
            'vendor', 
            'vendor.currency',
            'wholesaleTiers'
        ])->find($vendor_product_id);

        if (!$vendorProduct) {
            return null;
        }

        // Get first product image
        $image = null;
        $productImages = $vendorProduct->product->images ?? [];
        if (is_array($productImages) && count($productImages)) {
            $image = $productImages[0];
        }

        // Get base price (from vendor_product)
        $basePrice = $vendorProduct->sale_price ?: $vendorProduct->price;

        return [
            'vendor_product_id' => $vendorProduct->id,
            'product_id' => $vendorProduct->product_id,
            'vendor_id' => $vendorProduct->vendor_id,
            'product_name' => $vendorProduct->product->name,
            'image' => $image,
            'base_price' => (float) $basePrice,
            'currency' => $vendorProduct->vendor->currency->code,
            'rate_to_usd' => $vendorProduct->vendor->currency->rate_to_usd ?? 1,
            'wholesale_tiers' => $vendorProduct->wholesaleTiers,
            'variation_json' => $vendorProduct->variation_json ?? [],
            'vendor' => [
                'id' => $vendorProduct->vendor->id,
                'store_name' => $vendorProduct->vendor->store_name,
            ]
        ];
    }

    /**
     * Parse selected variations from array to note string
     */
    static private function parseVariationsToNote($selectedVariations = [], $variation_json = [])
    {
        if (empty($selectedVariations)) {
            return '';
        }

        $noteParts = [];
        
        foreach ($selectedVariations as $attribute => $value) {
            // Check if this attribute exists in the variation_json
            if (isset($variation_json[$attribute])) {
                $noteParts[] = ucfirst($attribute) . ': ' . $value;
            }
        }
        
        return implode(', ', $noteParts);
    }

    /**
     * Calculate wholesale price for given quantity
     */
    static private function calculateWholesalePrice($wholesaleTiers, $basePrice, $quantity)
    {
        if (!$wholesaleTiers || $wholesaleTiers->isEmpty()) {
            return (float) $basePrice;
        }

        // Find the applicable tier
        $applicableTier = null;
        foreach ($wholesaleTiers->sortBy('min_qty') as $tier) {
            if ($quantity >= $tier->min_qty && 
                (is_null($tier->max_qty) || $quantity <= $tier->max_qty)) {
                $applicableTier = $tier;
            }
        }

        return $applicableTier ? (float) $applicableTier->price : (float) $basePrice;
    }

    
    /**
     * Generate unique cart key based on vendor product and variation note
     */
    static private function generateCartKey($vendor_product_id, $variation_note = '')
    {
        // Use variation note if provided, otherwise just vendor_product_id
        return 'cart_' . $vendor_product_id . '_' . md5($variation_note);
    }

    /**
     * Add item to cart with vendor_product_id and optional variations
     */
    static public function addItemToCart($vendor_product_id, $quantity = 1, $selectedVariations = [], $custom_note = '')
    {
        $cart_items = self::getCartItemsFromCookie();
        $productData = self::getVendorProductData($vendor_product_id);
        
        if (!$productData) {
            return count($cart_items);
        }

        // Parse variations to note string
        $variationNote = self::parseVariationsToNote($selectedVariations, $productData['variation_json']);
        
        // Add custom note if provided
        if (!empty($custom_note)) {
            if (!empty($variationNote)) {
                $variationNote .= ' | ' . $custom_note;
            } else {
                $variationNote = $custom_note;
            }
        }

        // Generate cart key BEFORE checking existing items
        $cartKey = self::generateCartKey($vendor_product_id, $variationNote);
        
        // Check if item already exists in cart (same vendor_product + same variation note)
        $existingKey = null;
        foreach ($cart_items as $key => $item) {
            if (isset($item['cart_key']) && $item['cart_key'] === $cartKey) {
                $existingKey = $key;
                break;
            }
        }

        if ($existingKey !== null) {
            // Update existing item (same variation note)
            $newQuantity = $cart_items[$existingKey]['quantity'] + $quantity;
            
            $cart_items[$existingKey]['quantity'] = $newQuantity;
        } else {
            // Add new item (new variation note combination)
            $cart_items[] = [
                'vendor_product_id' => $vendor_product_id,
                'product_id' => $productData['product_id'],
                'vendor_id' => $productData['vendor_id'],
                'product_name' => $productData['product_name'],
                'image' => $productData['image'],
                'quantity' => $quantity,
                'base_price' => $productData['base_price'], // Store original base price
                'currency' => $productData['currency'],
                'rate_to_usd' => $productData['rate_to_usd'],
                'selected_variations' => $selectedVariations,
                'variation_note' => $variationNote,
                'wholesale_applied' => false,
                'cart_key' => $cartKey,
                'wholesale_tiers' => $productData['wholesale_tiers']->toArray(),
            ];
        }

        // Update wholesale prices for ALL items of this vendor_product
        self::updateWholesalePricesForVendorProduct($cart_items, $vendor_product_id, $productData);
        
        self::addCartItemsToCookie($cart_items);
        return count($cart_items);
    }
    // Update the getTotalVendorProductQuantity method
    /**
         * Get total quantity of a vendor_product in cart (across all variations)
         */
        
    static private function getTotalVendorProductQuantity($cart_items, $vendor_product_id)
    {
        $totalQuantity = 0;
        foreach ($cart_items as $item) {
            // Check if vendor_product_id exists and matches
            if (isset($item['vendor_product_id']) && $item['vendor_product_id'] == $vendor_product_id) {
                $totalQuantity += $item['quantity'] ?? 0;
            }
        }
        return $totalQuantity;
    }

    // Also update the updateWholesalePricesForVendorProduct method for consistency
    static private function updateWholesalePricesForVendorProduct(&$cart_items, $vendor_product_id, $productData)
    {
        $totalQuantity = self::getTotalVendorProductQuantity($cart_items, $vendor_product_id);
        
        // Calculate new wholesale price
        $newWholesalePrice = self::calculateWholesalePrice(
            $productData['wholesale_tiers'],
            $productData['base_price'],
            $totalQuantity
        );

        // Update all items of this vendor_product
        foreach ($cart_items as &$item) {
            // Check if vendor_product_id exists before comparing
            if (isset($item['vendor_product_id']) && $item['vendor_product_id'] == $vendor_product_id) {
                $item['unit_amount'] = $newWholesalePrice;
                $item['total_amount'] = $newWholesalePrice * $item['quantity'];
                $item['wholesale_applied'] = abs($newWholesalePrice - $productData['base_price']) > 0.001;
            }
        }
    }

    // Add this method to CartManagement.php
    static public function cleanupInvalidCartItems()
    {
        $cart_items = self::getCartItemsFromCookie();
        $cleaned_items = [];
        
        foreach ($cart_items as $item) {
            // Only keep items that have all required fields
            if (isset($item['vendor_product_id']) && 
                isset($item['quantity']) && 
                isset($item['cart_key'])) {
                $cleaned_items[] = $item;
            }
        }
        
        if (count($cart_items) !== count($cleaned_items)) {
            self::addCartItemsToCookie($cleaned_items);
        }
        
        return $cleaned_items;
    }

    /**
     * Remove item from cart using cart_key
     */

    static public function removeCartItem($cart_key)
    {
        $cart_items = self::getCartItemsFromCookie();

        // Get vendor_product_id before removing
        $vendor_product_id = null;
        $removed = false;
        
        foreach ($cart_items as $key => $item) {
            if (($item['cart_key'] ?? '') === $cart_key) {
                $vendor_product_id = $item['vendor_product_id'];
                unset($cart_items[$key]);
                $removed = true;
                break;
            }
        }

        // Reset array keys
        $cart_items = array_values($cart_items);

        if ($removed && $vendor_product_id) {
            // Update wholesale prices for remaining items
            $productData = self::getVendorProductData($vendor_product_id);
            if ($productData) {
                self::updateWholesalePricesForVendorProduct($cart_items, $vendor_product_id, $productData);
            }
        }

        // Save to cookie
        self::addCartItemsToCookie($cart_items);
        
        // Return the updated cart items
        return $cart_items;
    }

    /**
     * Clear all cart items
     */
    static public function clearCartItems()
    {
        Cookie::queue(Cookie::forget('cart_items'));
    }

    /**
     * Increment quantity for specific cart item
     */
    static public function incrementQuantity($cart_key)
    {
        $cart_items = self::getCartItemsFromCookie();
        $vendor_product_id = null;

        foreach ($cart_items as $key => &$item) {
            if (($item['cart_key'] ?? '') === $cart_key) {
                $vendor_product_id = $item['vendor_product_id'];
                $item['quantity']++;
                break;
            }
        }

        if ($vendor_product_id) {
            // Update wholesale prices for all items of this vendor_product
            $productData = self::getVendorProductData($vendor_product_id);
            if ($productData) {
                self::updateWholesalePricesForVendorProduct($cart_items, $vendor_product_id, $productData);
            }
        }

        self::addCartItemsToCookie($cart_items);
        return $cart_items;
    }

    /**
     * Decrement quantity for specific cart item
     */
    static public function decrementQuantity($cart_key)
    {
        $cart_items = self::getCartItemsFromCookie();
        $vendor_product_id = null;

        foreach ($cart_items as $key => &$item) {
            if (($item['cart_key'] ?? '') === $cart_key && $item['quantity'] > 1) {
                $vendor_product_id = $item['vendor_product_id'];
                $item['quantity']--;
                break;
            }
        }

        if ($vendor_product_id) {
            // Update wholesale prices for all items of this vendor_product
            $productData = self::getVendorProductData($vendor_product_id);
            if ($productData) {
                self::updateWholesalePricesForVendorProduct($cart_items, $vendor_product_id, $productData);
            }
        }

        self::addCartItemsToCookie($cart_items);
        return $cart_items;
    }

    /**
     * Get total cart count
     */
    static public function getCartCount(): int
    {
        $cart_items = self::getCartItemsFromCookie();
        $totalCount = 0;
        
        foreach ($cart_items as $item) {
            $totalCount += $item['quantity'] ?? 0;
        }
        
        return $totalCount;
    }

    /**
     * Calculate grand total in USD
     */
    static public function calculateGrandTotalUSD($cart_items = null)
    {
        $items = $cart_items ?? self::getCartItemsFromCookie();
        $totalUSD = 0;

        foreach ($items as $item) {
            $rate = $item['rate_to_usd'] ?? 1;
            $totalUSD += ($item['total_amount'] ?? 0) * $rate;
        }

        return $totalUSD;
    }

    /**
     * Group cart items by vendor with subtotals
     */
    static public function groupCartByVendor($cart_items = null)
    {
        $items = $cart_items ?? self::getCartItemsFromCookie();
        $grouped = [];

        foreach ($items as $item) {
            $vendorId = $item['vendor_id'];
            
            if (!isset($grouped[$vendorId])) {
                $grouped[$vendorId] = [
                    'vendor_id' => $vendorId,
                    'items' => [],
                    'subtotal' => 0,
                    'subtotal_usd' => 0,
                ];
            }

            $itemTotalUSD = ($item['total_amount'] ?? 0) * ($item['rate_to_usd'] ?? 1);
            
            $grouped[$vendorId]['items'][] = $item;
            $grouped[$vendorId]['subtotal'] += $item['total_amount'] ?? 0;
            $grouped[$vendorId]['subtotal_usd'] += $itemTotalUSD;
        }

        return $grouped;
    }

    /**
     * Get cart summary
     */
    static public function getCartSummary()
    {
        $cart_items = self::getCartItemsFromCookie();
        $grouped = self::groupCartByVendor($cart_items);
        
        return [
            'items' => $cart_items,
            'grouped' => $grouped,
            'total_items' => count($cart_items),
            'total_vendors' => count($grouped),
            'grand_total_usd' => self::calculateGrandTotalUSD($cart_items),
        ];
    }

    /**
     * Update quantity for specific cart item
     */
    static public function updateQuantity($cart_key, $new_quantity)
    {
        $cart_items = self::getCartItemsFromCookie();
        $vendor_product_id = null;

        foreach ($cart_items as $key => &$item) {
            if (($item['cart_key'] ?? '') === $cart_key) {
                $vendor_product_id = $item['vendor_product_id'];
                $item['quantity'] = max(1, (int) $new_quantity);
                break;
            }
        }

        if ($vendor_product_id) {
            // Update wholesale prices for all items of this vendor_product
            $productData = self::getVendorProductData($vendor_product_id);
            if ($productData) {
                self::updateWholesalePricesForVendorProduct($cart_items, $vendor_product_id, $productData);
            }
        }

        self::addCartItemsToCookie($cart_items);
        return $cart_items;
    }

    /**
     * Get cart item count for navbar (sum of quantities)
     */
    static public function getNavbarCartCount()
    {
        $cart_items = self::getCartItemsFromCookie();
        $total = 0;
        
        foreach ($cart_items as $item) {
            $total += $item['quantity'] ?? 0;
        }
        
        return $total;
    }
}