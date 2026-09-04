<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\WishlistManagement;
use App\Models\VendorProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Get user's wishlist
     */
    public function index()
    {
        try {
            $wishlistItems = WishlistManagement::getWishlistDetails();

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $wishlistItems,
                    'count' => count($wishlistItems)
                ],
                'message' => 'Wishlist retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve wishlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add item to wishlist
     */
    public function add($vendorProductId, Request $request)
    {
        try {
            // DEBUG: Log the incoming request
            \Log::info('🔵 Wishlist add request:', [
                'vendorProductId' => $vendorProductId,
                'all_data' => $request->all(),
                'variation_id' => $request->variation_id,
                'selected_attributes' => $request->selected_attributes,
            ]);

            // Verify product exists
            $product = VendorProduct::with('product')->find($vendorProductId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Add to wishlist - this returns a boolean or array?
            $result = WishlistManagement::addItem(
                $vendorProductId,
                $request->variation_id,
                $request->selected_attributes ?? []
            );

            // Get the wishlist key - depends on what your helper returns
            $wishlistKey = null;
            if (is_array($result) && isset($result['wishlist_key'])) {
                $wishlistKey = $result['wishlist_key'];
            } else {
                // If your helper returns just a boolean, generate the key yourself
                $wishlistKey = 'wish_' . $vendorProductId . '_simple';
                if ($request->variation_id) {
                    $wishlistKey = 'wish_' . $vendorProductId . '_var_' . $request->variation_id;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Item added to wishlist successfully',
                'data' => [
                    'wishlist_key' => $wishlistKey,
                    'count' => WishlistManagement::getCount()
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('🔴 Wishlist add exception:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to wishlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from wishlist
     */
    public function remove($vendorProductId)
    {
        try {
            // Find and remove the item
            $wishlist = WishlistManagement::getWishlist();
            $removed = false;

            foreach ($wishlist as $key => $item) {
                if ($item['vendor_product_id'] == $vendorProductId) {
                    WishlistManagement::removeItem($item['wishlist_key']);
                    $removed = true;
                    break;
                }
            }

            if ($removed) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item removed from wishlist',
                    'data' => [
                        'count' => WishlistManagement::getCount()
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Item not found in wishlist'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item from wishlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item by wishlist key
     */
    public function removeByKey($wishlistKey)
    {
        try {
            WishlistManagement::removeItem($wishlistKey);

            return response()->json([
                'success' => true,
                'message' => 'Item removed from wishlist',
                'data' => [
                    'count' => WishlistManagement::getCount()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item from wishlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear entire wishlist
     */
    public function clear()
    {
        try {
            WishlistManagement::clear();

            return response()->json([
                'success' => true,
                'message' => 'Wishlist cleared successfully',
                'data' => [
                    'count' => 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear wishlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move wishlist items to cart
     */
    public function moveToCart(Request $request)
    {
        try {
            $wishlistKeys = $request->wishlist_keys ?? [];
            
            if (empty($wishlistKeys)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items selected'
                ], 400);
            }

            $wishlist = WishlistManagement::getWishlistDetails();
            $moved = 0;

            foreach ($wishlist as $item) {
                if (in_array($item['wishlist_key'], $wishlistKeys)) {
                    \App\Helpers\CartManagement::addItemToCart(
                        vendor_product_id: $item['vendor_product_id'],
                        quantity: 1,
                        variation_id: $item['variation_id'] ?? null,
                        selectedAttributes: $item['selected_variations'] ?? []
                    );
                    WishlistManagement::removeItem($item['wishlist_key']);
                    $moved++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "$moved items moved to cart",
                'data' => [
                    'wishlist_count' => WishlistManagement::getCount(),
                    'cart_count' => \App\Helpers\CartManagement::getCartCount()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to move items to cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}