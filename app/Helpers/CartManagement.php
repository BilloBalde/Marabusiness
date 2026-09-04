<?php

namespace App\Helpers;

use App\Models\CartItem;
use App\Models\VendorProduct;
use App\Models\VendorProductVariation;
use Illuminate\Support\Facades\Auth;

class CartManagement
{
    /**
     * Get cart items from database (authenticated users only)
     */
    static public function getCartItemsFromCookie()
    {
        if (!Auth::check()) {
            return [];
        }

        $cart_items = [];
        $userId = Auth::id();
        $items = CartItem::where('user_id', $userId)->get();

        foreach ($items as $item) {
            $productData = self::getVendorProductData(
                $item->vendor_product_id,
                $item->variation_id
            );

            if (!$productData) {
                continue;
            }

            $selectedVariations = $item->selected_variations ?? [];
            $variationNote = '';

            if ($productData['has_variations']) {
                if ($productData['variation']) {
                    $variationNote = self::formatVariationAttributes($productData['variation']->attributes ?? []);
                } elseif (!empty($selectedVariations)) {
                    $variationNote = self::formatVariationAttributes($selectedVariations);
                }
            }

            if (!empty($item->custom_note)) {
                $variationNote = $variationNote ? $variationNote . ' | ' . $item->custom_note : $item->custom_note;
            }

            $unitAmount = self::calculateWholesalePrice(
                $productData['wholesale_tiers'],
                $productData['base_price'],
                $item->quantity
            );

            $cart_items[] = [
                'vendor_product_id' => $productData['vendor_product_id'],
                'product_id' => $productData['product_id'],
                'vendor_id' => $productData['vendor_id'],
                'product_name' => $productData['product_name'],
                'image' => $productData['image'],
                'quantity' => (int) $item->quantity,
                'base_price' => $productData['base_price'],
                'currency' => $productData['currency'],
                'rate_to_usd' => $productData['rate_to_usd'],
                'variation_id' => $item->variation_id,
                'selected_variations' => $selectedVariations,
                'variation_note' => $variationNote,
                'wholesale_applied' => abs($unitAmount - $productData['base_price']) > 0.001,
                'cart_key' => $item->cart_key ?: self::generateCartKey(
                    $productData['vendor_product_id'],
                    $item->variation_id,
                    $selectedVariations
                ),
                'wholesale_tiers' => $productData['wholesale_tiers'] ? $productData['wholesale_tiers']->toArray() : [],
                'unit_amount' => $unitAmount,
                'total_amount' => $unitAmount * $item->quantity,
            ];
        }

        return $cart_items;
    }

    /**
     * Save cart items to cookie
     */
    /* static public function addCartItemsToCookie($cart_items)
    {
        $cookie = cookie('cart_items', json_encode(array_values($cart_items)), 60 * 24 * 30);
        Cookie::queue($cookie);
        return true;
    } */
    static public function addCartItemsToCookie($cart_items, $force = false)
    {
        if (!Auth::check()) {
            return false;
        }

        $userId = Auth::id();
        $cart_items = array_values(is_array($cart_items) ? $cart_items : []);

        CartItem::where('user_id', $userId)->delete();

        foreach ($cart_items as $item) {
            if (!isset($item['vendor_product_id'])) {
                continue;
            }

            $variationId = $item['variation_id'] ?? null;
            $selectedVariations = $item['selected_variations'] ?? [];

            CartItem::create([
                'user_id' => $userId,
                'vendor_product_id' => $item['vendor_product_id'],
                'variation_id' => $variationId,
                'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : 1,
                'selected_variations' => $selectedVariations,
                'custom_note' => $item['custom_note'] ?? null,
                'cart_key' => $item['cart_key'] ?? self::generateCartKey(
                    $item['vendor_product_id'],
                    $variationId,
                    $selectedVariations
                ),
            ]);
        }

        return true;
    }

    /**
     * Get vendor product data with variation support
     */
    static private function getVendorProductData($vendor_product_id, $variation_id = null)
    {
        $request_id = uniqid('req_', true);
        try {
            $vendorProduct = VendorProduct::with([
                'product', 
                'vendor', 
                'vendor.currency',
                'wholesaleTiers',
                'variations',
                'variations.wholesaleTiers',
            ])->find($vendor_product_id);

            if (!$vendorProduct) {
                \Log::warning('CartManagement: Vendor product not found', ['id' => $vendor_product_id]);
                return null;
            }

            // REMOVE THIS ERRONEOUS LINE:
            // \Log::info('CartManagement addCartItemsToCookie: Completed', [
            //     'request_id' => $request_id,
            //     'final_item_count' => count($cart_items)
            // ]);

            // Or if you want to keep some logging, use correct variables:
            \Log::info('CartManagement getVendorProductData: Retrieved product', [
                'request_id' => $request_id,
                'vendor_product_id' => $vendor_product_id,
                'variation_id' => $variation_id,
                'product_name' => $vendorProduct->product->name ?? 'Unknown'
            ]);

            // Initialize defaults
            $price = $vendorProduct->sale_price ?: $vendorProduct->price;
            $stock = $vendorProduct->stock;
            $variation = null;

            if ($variation_id) {
                // Try to find the variation
                $variation = $vendorProduct->variations
                    ->where('id', $variation_id)
                    ->first();
                
                if ($variation) {
                    $price = $variation->price;
                    $stock = $variation->stock;
                    \Log::info('CartManagement: Found variation', [
                        'variation_id' => $variation_id,
                        'price' => $price,
                        'stock' => $stock
                    ]);
                } else {
                    \Log::warning('CartManagement: Variation not found', [
                        'vendor_product_id' => $vendor_product_id,
                        'variation_id' => $variation_id
                    ]);
                    // Variation not found, but we'll continue with base product
                }
            } elseif ($vendorProduct->has_variations) {
                // Default to a first available variation when none is provided
                $variation = $vendorProduct->variations
                    ->firstWhere('stock', '>', 0) ?? $vendorProduct->variations->first();

                if ($variation) {
                    $variation_id = $variation->id;
                    $price = $variation->price;
                    $stock = $variation->stock;
                    \Log::info('CartManagement: Defaulted variation', [
                        'variation_id' => $variation_id,
                        'price' => $price,
                        'stock' => $stock
                    ]);
                }
            }

            // Get first product image
            $image = null;
            $productImages = $vendorProduct->product->images ?? [];
            if (is_array($productImages) && count($productImages)) {
                $image = $productImages[0];
            }

            return [
                'vendor_product_id' => $vendorProduct->id,
                'product_id' => $vendorProduct->product_id,
                'vendor_id' => $vendorProduct->vendor_id,
                'product_name' => $vendorProduct->product->name ?? 'Unknown Product',
                'image' => $image,
                'base_price' => (float) $price,
                'stock' => $stock,
                'currency' => $vendorProduct->vendor->currency->code ?? 'USD',
                'rate_to_usd' => $vendorProduct->vendor->currency->rate_to_usd ?? 1,
                'wholesale_tiers' => ($vendorProduct->has_variations && $variation)
                    ? ($variation->wholesaleTiers ?? collect([]))     // ✅ variation tiers
                    : ($vendorProduct->wholesaleTiers ?? collect([])) // ✅ simple product tiers
                ,
                'has_variations' => $vendorProduct->has_variations,
                'variation_id' => $variation_id,
                'variation' => $variation,
                'vendor' => [
                    'id' => $vendorProduct->vendor->id,
                    'store_name' => $vendorProduct->vendor->store_name,
                ]
            ];

        } catch (\Exception $e) {
            \Log::error('CartManagement: Error getting product data', [
                'vendor_product_id' => $vendor_product_id,
                'variation_id' => $variation_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Generate unique cart key based on vendor product and variation
     */
    static private function generateCartKey($vendor_product_id, $variation_id = null, $selectedVariations = [])
    {
        if ($variation_id) {
            return 'cart_' . $vendor_product_id . '_var_' . $variation_id;
        }
        
        // If no variation ID but we have selected variations, create a hash
        if (!empty($selectedVariations)) {
            ksort($selectedVariations); // Sort for consistency
            return 'cart_' . $vendor_product_id . '_' . md5(json_encode($selectedVariations));
        }
        
        return 'cart_' . $vendor_product_id . '_simple';
    }

    /**
     * Calculate wholesale price for given quantity
     */
    /* static private function calculateWholesalePrice($wholesaleTiers, $basePrice, $quantity)
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
    } */

    /**
     * Add item to cart with variation support
     * Returns array with status and message
     */
    /**
 * Add item to cart with variation support
 * Returns array with status and message
 */
static public function addItemToCart($vendor_product_id, $quantity = 1, $variation_id = null, $selectedAttributes = [], $custom_note = '') 
{
    try {
        if (!Auth::check()) {
            return [
                'success' => false,
                'message' => 'Please login to add items to cart.',
                'requires_auth' => true,
                'cart_count' => 0,
                'items_count' => 0
            ];
        }

        \Log::info('CartManagement: Adding item to cart', [
            'vendor_product_id' => $vendor_product_id,
            'quantity' => $quantity,
            'variation_id' => $variation_id,
            'selectedAttributes' => $selectedAttributes,
        ]);

        $productData = self::getVendorProductData($vendor_product_id, $variation_id);
        
        if (!$productData) {
            \Log::error('CartManagement: Could not get product data', [
                'vendor_product_id' => $vendor_product_id,
                'variation_id' => $variation_id
            ]);
            return [
                'success' => false,
                'message' => 'Product not found or error loading product data',
                'cart_count' => self::getCartCount(),
                'items_count' => count(self::getCartItemsFromCookie())
            ];
        }

        // Default variation when listing pages don't pass it
        if ($variation_id === null && ($productData['has_variations'] ?? false) && !empty($productData['variation'])) {
            $variation_id = $productData['variation']->id;
            if (empty($selectedAttributes) && !empty($productData['variation']->attributes)) {
                $selectedAttributes = $productData['variation']->attributes;
            }
        }

        // Generate cart key for the new item
        $newCartKey = self::generateCartKey($vendor_product_id, $variation_id, $selectedAttributes);

        // Check stock availability
        $availableStock = $productData['stock'];
        if ($availableStock > 0) {
            if ($quantity > $availableStock) {
                $quantity = $availableStock;
            }
        }

        $cartItem = CartItem::where('user_id', Auth::id())
            ->where('cart_key', $newCartKey)
            ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;
            if ($availableStock > 0 && $newQuantity > $availableStock) {
                $newQuantity = $availableStock;
            }

            if ($availableStock > 0 && $newQuantity <= 0) {
                return [
                    'success' => false,
                    'message' => "Stock limit reached! Only $availableStock available.",
                    'cart_count' => self::getCartCount(),
                    'items_count' => count(self::getCartItemsFromCookie())
                ];
            }

            $cartItem->quantity = $newQuantity;
        } else {
            if ($availableStock > 0 && $quantity > $availableStock) {
                $quantity = $availableStock;
            }

            $cartItem = new CartItem([
                'user_id' => Auth::id(),
                'vendor_product_id' => $vendor_product_id,
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'selected_variations' => $selectedAttributes,
                'custom_note' => $custom_note,
                'cart_key' => $newCartKey,
            ]);
        }

        $cartItem->selected_variations = $selectedAttributes;
        $cartItem->custom_note = $custom_note;
        $cartItem->variation_id = $variation_id;
        $cartItem->save();

        // Get updated cart count
        $updated_cart_items = self::getCartItemsFromCookie();
        
        \Log::info('CartManagement: Item added successfully', [
            'cart_key' => $newCartKey,
            'final_cart_count' => self::getCartCount(),
            'final_items_count' => count($updated_cart_items)
        ]);
        
        return [
            'success' => true,
            'message' => 'Item added to cart successfully!',
            'cart_count' => self::getCartCount(),
            'items_count' => count($updated_cart_items)
        ];
        
    } catch (\Exception $e) {
        \Log::error('CartManagement: Error in addItemToCart', [
            'vendor_product_id' => $vendor_product_id,
            'variation_id' => $variation_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'message' => 'An error occurred while adding item to cart. Please try again.',
            'cart_count' => self::getCartCount(),
            'items_count' => 0
        ];
    }
}
/**
 * Calculate wholesale price for given quantity (per variation)
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
 * Update wholesale prices for a specific cart item (per variation)
 */
static private function updateWholesalePriceForItem(&$item, $productData)
{
    if (!isset($item['vendor_product_id']) || !isset($item['cart_key'])) {
        return;
    }
    
    // Calculate wholesale price for THIS ITEM'S QUANTITY ONLY
    $itemQuantity = $item['quantity'] ?? 1;
    
    $newWholesalePrice = self::calculateWholesalePrice(
        $productData['wholesale_tiers'],
        $productData['base_price'],
        $itemQuantity  // Use individual item quantity
    );

    $item['unit_amount'] = $newWholesalePrice;
    $item['total_amount'] = $newWholesalePrice * $itemQuantity;
    $item['wholesale_applied'] = abs($newWholesalePrice - $productData['base_price']) > 0.001;
}

/**
 * Get total quantity for a specific variation combination
 */
static private function getTotalVariationQuantity($cart_items, $vendor_product_id, $cart_key)
{
    $totalQuantity = 0;
    foreach ($cart_items as $item) {
        if (isset($item['vendor_product_id']) && 
            $item['vendor_product_id'] == $vendor_product_id &&
            isset($item['cart_key']) &&
            $item['cart_key'] == $cart_key) {
            $totalQuantity += $item['quantity'] ?? 0;
        }
    }
    return $totalQuantity;
}

/**
 * Update wholesale prices for all cart items individually
 */
static private function updateWholesalePricesForCart(&$cart_items)
{
    // Group items by vendor_product_id and cart_key
    $productGroups = [];
    
    foreach ($cart_items as $item) {
        if (isset($item['vendor_product_id']) && isset($item['cart_key'])) {
            $key = $item['vendor_product_id'] . '_' . $item['cart_key'];
            if (!isset($productGroups[$key])) {
                $productGroups[$key] = [
                    'vendor_product_id' => $item['vendor_product_id'],
                    'cart_key' => $item['cart_key'],
                    'items' => [],
                    'total_quantity' => 0
                ];
            }
            $productGroups[$key]['items'][] = &$item;
            $productGroups[$key]['total_quantity'] += $item['quantity'] ?? 0;
        }
    }
    
    // Update each group individually
    foreach ($productGroups as $group) {
        if (!empty($group['items'])) {
            // Get product data for this vendor product
            $productData = self::getVendorProductData($group['vendor_product_id']);
            
            if ($productData) {
                // Calculate wholesale price for this specific group
                $groupWholesalePrice = self::calculateWholesalePrice(
                    $productData['wholesale_tiers'],
                    $productData['base_price'],
                    $group['total_quantity']
                );
                
                // Apply to all items in this group
                foreach ($group['items'] as &$item) {
                    $item['unit_amount'] = $groupWholesalePrice;
                    $item['total_amount'] = $groupWholesalePrice * $item['quantity'];
                    $item['wholesale_applied'] = abs($groupWholesalePrice - $productData['base_price']) > 0.001;
                }
            }
        }
    }
}

/**
 * Update quantity for specific cart item
 */
    static public function updateQuantity($cart_key, $new_quantity)
    {
        if (!Auth::check()) {
            return [];
        }

        $item = CartItem::where('user_id', Auth::id())
            ->where('cart_key', $cart_key)
            ->first();

        if (!$item) {
            return self::getCartItemsFromCookie();
        }

        $new_quantity = max(1, (int) $new_quantity);
        $productData = self::getVendorProductData($item->vendor_product_id, $item->variation_id);
        $availableStock = $productData['stock'] ?? null;
        if ($availableStock && $new_quantity > $availableStock) {
            $new_quantity = $availableStock;
        }

        $item->quantity = $new_quantity;
        $item->save();

        return self::getCartItemsFromCookie();
    }

/**
 * Remove item from cart using cart_key
 */
static public function removeCartItem($cart_key)
{
    if (!Auth::check()) {
        return [];
    }

    CartItem::where('user_id', Auth::id())
        ->where('cart_key', $cart_key)
        ->delete();

    return self::getCartItemsFromCookie();
}
    /**
     * Format variation attributes for display
     */
    static private function formatVariationAttributes($attributes)
    {
        if (empty($attributes)) {
            return '';
        }
        
        $parts = [];
        foreach ($attributes as $attribute => $value) {
            $parts[] = ucfirst($attribute) . ': ' . $value;
        }
        
        return implode(', ', $parts);
    }

    /**
     * Update wholesale prices for EACH ITEM INDIVIDUALLY
     * (not combined across variations)
     */
    static private function updateWholesalePricesForEachItem(&$cart_items, $vendor_product_id, $productData)
    {
        foreach ($cart_items as &$item) {
            if (isset($item['vendor_product_id']) && $item['vendor_product_id'] == $vendor_product_id) {
                // Calculate wholesale price for THIS ITEM'S QUANTITY ONLY
                $itemQuantity = $item['quantity'] ?? 1;
                
                $newWholesalePrice = self::calculateWholesalePrice(
                    $productData['wholesale_tiers'],
                    $productData['base_price'],
                    $itemQuantity  // Use individual item quantity
                );

                $item['unit_amount'] = $newWholesalePrice;
                $item['total_amount'] = $newWholesalePrice * $itemQuantity;
                $item['wholesale_applied'] = abs($newWholesalePrice - $productData['base_price']) > 0.001;
            }
        }
    }

    /**
     * Get total quantity of a vendor_product in cart (across all variations)
     */
    static private function getTotalVendorProductQuantity($cart_items, $vendor_product_id)
    {
        $totalQuantity = 0;
        foreach ($cart_items as $item) {
            if (isset($item['vendor_product_id']) && $item['vendor_product_id'] == $vendor_product_id) {
                $totalQuantity += $item['quantity'] ?? 0;
            }
        }
        return $totalQuantity;
    }

    /**
     * Update wholesale prices for all items of a vendor product
     */
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

    /* static public function removeCartItem($cart_key)
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
    } */

    /**
     * Clear all cart items
     */
    static public function clearCartItems()
    {
        if (Auth::check()) {
            CartItem::where('user_id', Auth::id())->delete();
        }
    }

    /**
     * Increment quantity for specific cart item
     */
    static public function incrementQuantity($cart_key)
    {
        if (!Auth::check()) {
            return [];
        }

        $item = CartItem::where('user_id', Auth::id())
            ->where('cart_key', $cart_key)
            ->first();

        if (!$item) {
            return self::getCartItemsFromCookie();
        }

        $new_quantity = $item->quantity + 1;
        $productData = self::getVendorProductData($item->vendor_product_id, $item->variation_id);
        $availableStock = $productData['stock'] ?? null;
        if ($availableStock && $new_quantity > $availableStock) {
            $new_quantity = $availableStock;
        }

        $item->quantity = $new_quantity;
        $item->save();

        return self::getCartItemsFromCookie();
    }

    /**
     * Decrement quantity for specific cart item
     */
    static public function decrementQuantity($cart_key)
    {
        if (!Auth::check()) {
            return [];
        }

        $item = CartItem::where('user_id', Auth::id())
            ->where('cart_key', $cart_key)
            ->first();

        if (!$item) {
            return self::getCartItemsFromCookie();
        }

        $new_quantity = max(1, $item->quantity - 1);
        $item->quantity = $new_quantity;
        $item->save();

        return self::getCartItemsFromCookie();
    }

    /**
     * Get total cart count
     */
    static public function getCartCount(): int
    {
        if (!Auth::check()) {
            return 0;
        }

        return (int) CartItem::where('user_id', Auth::id())->sum('quantity');
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
            $vendorId = $item['vendor_id'] ?? null;
            if (!$vendorId) {
                continue;
            }
            
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
    /* static public function updateQuantity($cart_key, $new_quantity)
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
    } */

    /**
     * Get cart item count for navbar (sum of quantities)
     */
    static public function getNavbarCartCount()
    {
        return self::getCartCount();
    }
}
