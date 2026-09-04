<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\CartManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * Get cart items (GET /api/cart)
     */
    // In CartController.php, update the index method

    // In CartController.php - Update the index method

    public function index(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                    'cart' => [
                        'vendors' => [],
                        'total_items' => 0,
                        'total_vendors' => 0,
                        'subtotal_usd' => 0,
                    ]
                ], 401);
            }

            // Get cart items from database
            $cartItems = CartManagement::getCartItemsFromCookie();
            
            // Group by vendor
            $groupedCart = CartManagement::groupCartByVendor($cartItems);
            
            // Format grouped cart for API response
            $vendors = [];
            $totalItems = 0;
            $subtotalUSD = 0;
            
            foreach ($groupedCart as $vendorId => $group) {
                // Get vendor from database to ensure correct name
                $vendor = \App\Models\Vendor::find($vendorId);
                $vendorName = $vendor ? $vendor->store_name : 'Vendeur inconnu';
                
                $vendorItems = [];
                $vendorSubtotalUSD = 0;
                
                foreach ($group['items'] as $item) {
                    $vendorSubtotalUSD += ($item['total_amount'] ?? 0) * ($item['rate_to_usd'] ?? 1);
                    $totalItems += $item['quantity'] ?? 1;
                    
                    // Add vendor_name to each item for fallback
                    $item['vendor_name'] = $vendorName;
                    
                    $vendorItems[] = [
                        'cart_key' => $item['cart_key'],
                        'vendor_product_id' => $item['vendor_product_id'],
                        'product_id' => $item['product_id'],
                        'vendor_id' => $vendorId,
                        'vendor_name' => $vendorName, // Add vendor name to item
                        'product_name' => $item['product_name'],
                        'image' => $item['image'],
                        'quantity' => $item['quantity'],
                        'unit_amount' => $item['unit_amount'],
                        'total_amount' => $item['total_amount'],
                        'currency' => $item['currency'],
                        'rate_to_usd' => $item['rate_to_usd'],
                        'variation_id' => $item['variation_id'] ?? null,
                        'variation_note' => $item['variation_note'] ?? '',
                        'selected_variations' => $item['selected_variations'] ?? [],
                        'wholesale_applied' => $item['wholesale_applied'] ?? false,
                    ];
                }
                
                $vendors[] = [
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendorName, // Use actual vendor name
                    'vendor_logo' => $vendor ? $vendor->logo_path : null,
                    'currency' => $group['currency'] ?? 'USD',
                    'items' => $vendorItems,
                    'subtotal' => $group['subtotal'] ?? 0,
                    'subtotal_usd' => $vendorSubtotalUSD,
                ];
                
                $subtotalUSD += $vendorSubtotalUSD;
            }

            return response()->json([
                'success' => true,
                'message' => 'Cart retrieved successfully',
                'cart' => [
                    'vendors' => $vendors,
                    'total_items' => $totalItems,
                    'total_vendors' => count($vendors),
                    'subtotal_usd' => $subtotalUSD,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('CartController@index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add item to cart (POST /api/cart/add)
     */
    public function add(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to add items to cart',
                    'requires_auth' => true
                ], 401);
            }

            $request->validate([
                'vendor_product_id' => 'required|integer',
                'quantity' => 'required|integer|min:1',
                'variation_id' => 'nullable|integer',
                'selected_variations' => 'nullable|array',
                'custom_note' => 'nullable|string|max:500',
            ]);

            $quantity = (int) $request->quantity;
            if ($quantity < 1) $quantity = 1;

            // Add to cart using CartManagement (same as Livewire)
            $result = CartManagement::addItemToCart(
                vendor_product_id: $request->vendor_product_id,
                quantity: $quantity,
                variation_id: $request->variation_id,
                selectedAttributes: $request->selected_variations ?? [],
                custom_note: $request->custom_note ?? ''
            );

            if (!$result || !is_array($result)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error adding item to cart. Please try again.'
                ], 500);
            }

            if ($result['success']) {
                // Get updated cart count
                $cartCount = CartManagement::getCartCount();
                
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'cart_count' => $cartCount,
                    'cart_key' => $result['cart_key'] ?? null
                ]);
            } else {
                // Check if it's a stock issue
                if (isset($result['stock']) && $result['stock'] > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Only {$result['stock']} items available in stock.",
                        'stock_limit' => $result['stock']
                    ], 400);
                }

                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('CartController@add error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding item to cart'
            ], 500);
        }
    }

    /**
     * Update cart item quantity (PUT /api/cart/update/{cart_key})
     */
    public function update(Request $request, $cart_key)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $request->validate([
                'quantity' => 'required|integer|min:1'
            ]);

            $quantity = (int) $request->quantity;
            if ($quantity < 1) $quantity = 1;

            // Update quantity using CartManagement
            $cartItems = CartManagement::updateQuantity($cart_key, $quantity);

            // Verify the item was updated
            $itemUpdated = false;
            $newQuantity = $quantity;
            foreach ($cartItems as $item) {
                if (($item['cart_key'] ?? null) === $cart_key) {
                    $itemUpdated = true;
                    $newQuantity = $item['quantity'] ?? $quantity;
                    break;
                }
            }

            if (!$itemUpdated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found in cart'
                ], 404);
            }

            // Get updated cart count
            $cartCount = CartManagement::getCartCount();

            return response()->json([
                'success' => true,
                'message' => 'Cart updated successfully',
                'cart_count' => $cartCount,
                'quantity' => $newQuantity,
                'cart_key' => $cart_key
            ]);

        } catch (\Exception $e) {
            Log::error('CartController@update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'cart_key' => $cart_key
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart'
            ], 500);
        }
    }

    /**
     * Remove item from cart (DELETE /api/cart/remove/{cart_key})
     */
    public function remove($cart_key)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Remove item using CartManagement
            $cartItems = CartManagement::removeCartItem($cart_key);

            // Get updated cart count
            $cartCount = CartManagement::getCartCount();

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart',
                'cart_count' => $cartCount,
                'cart_key' => $cart_key
            ]);

        } catch (\Exception $e) {
            Log::error('CartController@remove error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'cart_key' => $cart_key
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item'
            ], 500);
        }
    }

    /**
     * Clear entire cart (POST /api/cart/clear)
     */
    public function clear()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Clear cart using CartManagement
            CartManagement::clearCartItems();

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully',
                'cart_count' => 0
            ]);

        } catch (\Exception $e) {
            Log::error('CartController@clear error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart'
            ], 500);
        }
    }

    /**
     * Get cart summary (GET /api/cart/summary)
     */
    public function summary()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $summary = CartManagement::getCartSummary();

            return response()->json([
                'success' => true,
                'message' => 'Cart summary retrieved successfully',
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('CartController@summary error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart summary'
            ], 500);
        }
    }

    /**
     * Get cart count only (GET /api/cart/count)
     */
    public function count()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'count' => 0
                ], 401);
            }

            $count = CartManagement::getCartCount();

            return response()->json([
                'success' => true,
                'count' => $count
            ]);

        } catch (\Exception $e) {
            Log::error('CartController@count error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'count' => 0
            ], 500);
        }
    }
}