<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Helpers\CartManagement;
use Illuminate\Support\Facades\Cookie;

class CartPage extends Component
{
    #[Title('Cart Page - MARA BUSINESS')]

    public $cart_items = [];
    public $grouped_cart = [];
    public $selected_items = []; // Contains cart_key values
    public $selected_ids = [];   // Contains vendor_product_id values for checkout
    public $selected_vendor_totals = [];
    public $selected_total = 0;
    public $selected_currency = 'USD';

    public function mount()
    {
          // Temporary: Clear corrupted cart data
    if (Cookie::has('cart_items')) {
        $cart_items = json_decode(Cookie::get('cart_items'), true);
        $hasCorruption = false;
        
        foreach ($cart_items as $item) {
            if (!isset($item['cart_key']) || !isset($item['vendor_product_id'])) {
                $hasCorruption = true;
                break;
            }
        }
        
        if ($hasCorruption) {
            // Clear corrupted cart
            Cookie::queue(Cookie::forget('cart_items'));
            $this->cart_items = [];
            return;
        }
    }
        $this->refreshCart();
        // Call this in your mount() methods or as a middleware
        //CartManagement::cleanupInvalidCartItems();
    }

    private function refreshCart()
    {
        $this->cart_items = CartManagement::getCartItemsFromCookie();
        
        // Clean up any items missing cart_key before processing
        $this->cart_items = array_filter($this->cart_items, function($item) {
            return isset($item['cart_key']);
        });

        $this->cart_items = array_values($this->cart_items); // Re-index array
        
        $this->grouped_cart = CartManagement::groupCartByVendor($this->cart_items);
        $this->updateSelectedSummary();
        $this->dispatch('cart-updated');
    }

    public function increaseQty($cart_key)
    {
        // Update in CartManagement
        $this->cart_items = CartManagement::incrementQuantity($cart_key);
        
        // Update local state without full refresh
        $this->updateLocalState($cart_key, 'increase');
        
        // Dispatch event but don't refresh cart completely
        $this->dispatch('cart-updated');
    }

    public function decreaseQty($cart_key)
    {
        $this->cart_items = CartManagement::decrementQuantity($cart_key);
        $this->updateLocalState($cart_key, 'decrease');
        $this->dispatch('cart-updated');
    }

    private function updateLocalState($cart_key, $action)
    {
        // Update the specific item in cart_items
        foreach ($this->cart_items as &$item) {
            if ($item['cart_key'] === $cart_key) {
                if ($action === 'increase') {
                    $item['quantity']++;
                } elseif ($action === 'decrease' && $item['quantity'] > 1) {
                    $item['quantity']--;
                }
                
                $item['total_amount'] = $item['unit_amount'] * $item['quantity'];
                
                // Update grouped cart
                $this->updateGroupedCart();
                
                // Update selected summary
                $this->updateSelectedSummary();
                break;
            }
        }
    }

    private function updateGroupedCart()
    {
        // Update grouped cart without full re-fetch
        $this->grouped_cart = [];
        
        foreach ($this->cart_items as $item) {
            $vendorId = $item['vendor_id'];
            
            if (!isset($this->grouped_cart[$vendorId])) {
                $this->grouped_cart[$vendorId] = [
                    'items' => [],
                    'subtotal' => 0,
                    'subtotal_usd' => 0
                ];
            }
            
            $this->grouped_cart[$vendorId]['items'][] = $item;
            $this->grouped_cart[$vendorId]['subtotal'] += $item['total_amount'];
            $this->grouped_cart[$vendorId]['subtotal_usd'] += ($item['total_amount'] * ($item['rate_to_usd'] ?? 1));
        }
    }

    public function removeItem($cart_key)
    {
        // Remove the item using CartManagement
        $this->cart_items = CartManagement::removeCartItem($cart_key);
        
        // Clean up any items missing cart_key
        $this->cart_items = array_filter($this->cart_items, function($item) {
            return isset($item['cart_key']);
        });
        $this->cart_items = array_values($this->cart_items);
        
        // IMPORTANT: Re-group the cart after removal
        $this->grouped_cart = CartManagement::groupCartByVendor($this->cart_items);
        
        // Remove the item from selected items if it was selected
        $key = array_search($cart_key, $this->selected_items);
        if ($key !== false) {
            unset($this->selected_items[$key]);
            $this->selected_items = array_values($this->selected_items);
        }
        
        // Update vendor checkboxes
        $this->updateVendorCheckboxes();
        
        // Update summary
        $this->updateSelectedSummary();
        
        // Dispatch events
        $this->dispatch('cart-removed');
        $this->dispatch('cart-updated');
        
        // If cart becomes empty, force a complete reset
        if (empty($this->cart_items)) {
            $this->selected_items = [];
            $this->selected_ids = [];
            $this->selected_vendor_totals = [];
            $this->selected_total = 0;
        }
    }

    public function updateQty($cart_key, $quantity)
    {
        if ($quantity < 1) $quantity = 1;
        $this->cart_items = CartManagement::updateQuantity($cart_key, $quantity);
        $this->refreshCart();
    }

    public function toggleVendor($vendor_id)
    {
        if (isset($this->selected_vendor_totals[$vendor_id])) {
            unset($this->selected_vendor_totals[$vendor_id]);
            
            foreach ($this->grouped_cart[$vendor_id]['items'] as $item) {
                $key = array_search($item['cart_key'], $this->selected_items);
                if ($key !== false) {
                    unset($this->selected_items[$key]);
                }
            }
        } else {
            $this->selected_vendor_totals[$vendor_id] = true;
            
            foreach ($this->grouped_cart[$vendor_id]['items'] as $item) {
                if (!in_array($item['cart_key'], $this->selected_items)) {
                    $this->selected_items[] = $item['cart_key'];
                }
            }
        }
        
        $this->selected_items = array_values($this->selected_items);
        $this->updateSelectedSummary();
    }

    public function toggleItem($cart_key)
    {
        $key = array_search($cart_key, $this->selected_items);
        
        if ($key !== false) {
            unset($this->selected_items[$key]);
            $this->updateVendorCheckboxes();
        } else {
            $this->selected_items[] = $cart_key;
            $this->updateVendorCheckboxes();
        }
        
        $this->selected_items = array_values($this->selected_items);
        $this->updateSelectedSummary();
    }

    private function updateVendorCheckboxes()
    {
        $this->selected_vendor_totals = [];
        
        $selectedByVendor = [];
        foreach ($this->selected_items as $cart_key) {
            foreach ($this->cart_items as $item) {
                if (($item['cart_key'] ?? '') === $cart_key) {
                    $selectedByVendor[$item['vendor_id']][] = $cart_key;
                    break;
                }
            }
        }
        
        foreach ($this->grouped_cart as $vendorId => $vendorGroup) {
            $vendorItemKeys = array_filter(array_map(function($item) {
                return $item['cart_key'] ?? null;
            }, $vendorGroup['items']));
            
            $selectedVendorItems = $selectedByVendor[$vendorId] ?? [];
            
            if (count($selectedVendorItems) === count($vendorItemKeys) && count($vendorItemKeys) > 0) {
                $this->selected_vendor_totals[$vendorId] = true;
            }
        }
    }

    public function updateSelectedSummary()
    {
        $totalUSD = 0;
        $this->selected_ids = []; // Reset selected_ids

        // Reset if no cart items
        if (empty($this->cart_items)) {
            $this->selected_items = [];
            $this->selected_vendor_totals = [];
            $this->selected_total = 0;
            $this->selected_currency = 'USD';
            return;
        }
        
        // Get all valid cart keys - ensure item has cart_key
        $validCartKeys = array_filter(array_map(function($item) {
            return $item['cart_key'] ?? null;
        }, $this->cart_items));
        
        // Filter out any selected items that don't exist anymore
        $this->selected_items = array_intersect($this->selected_items, $validCartKeys);
        $this->selected_items = array_values($this->selected_items);
        
        foreach ($this->cart_items as $item) {
            if (in_array($item['cart_key'] ?? null, $this->selected_items)) {
                $rate = $item['rate_to_usd'] ?? 1;
                $totalUSD += ($item['total_amount'] ?? 0) * $rate;
                
                // Store vendor_product_id for checkout
                if (isset($item['vendor_product_id'])) {
                    $this->selected_ids[] = $item['vendor_product_id'];
                }
            }
        }

        // Update vendor checkboxes based on current selection
        $this->updateVendorCheckboxes();
        
        $this->selected_total = $totalUSD;
        $this->selected_currency = 'USD';
    }

    /**
     * Get checkout URL with selected items
     */
    public function getCheckoutUrl(): string
    {
        if (empty($this->selected_ids)) {
            return '#';
        }
        
        // Pass vendor_product_id values to checkout
        return '/checkout?selected=' . urlencode(implode(',', $this->selected_ids));
    }

    /**
     * Get variation display text
     */
    public function getVariationText($item)
    {
        if (!empty($item['variation_note'])) {
            return $item['variation_note'];
        }
        
        if (!empty($item['selected_variations'])) {
            $variations = [];
            foreach ($item['selected_variations'] as $attribute => $value) {
                $variations[] = ucfirst($attribute) . ': ' . $value;
            }
            return implode(', ', $variations);
        }
        
        return '';
    }

    public function render()
    {
        return view('livewire.cart-page');
    }
}