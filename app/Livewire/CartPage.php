<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Helpers\CartManagement;

class CartPage extends Component
{
    #[Title('Cart Page - MARA BUSINESS')]

    public $cart_items = [];
    public $grouped_cart = [];
    public $selected_items = [];
    public $selected_ids = [];
    public $selected_vendor_totals = [];
    public $selected_total = 0;
    public $selected_currency = 'USD';
    public $quantity_values = [];
    public $showDeleteSelectedModal = false;

    public function mount()
    {
        \Log::info('CartPage mount: Starting cart refresh');
    
        $this->refreshCart();

        // Initialize quantity_values
        foreach ($this->cart_items as $item) {
            if (isset($item['cart_key'])) {
                $this->quantity_values[$item['cart_key']] = $item['quantity'] ?? 1;
            }
        }
        
        \Log::info('CartPage mount: Cart items after refresh', [
            'cart_items_count' => count($this->cart_items),
            'cart_items' => $this->cart_items,
            'grouped_cart_count' => count($this->grouped_cart)
        ]);
        /* $this->refreshCart();

        // Initialize quantity_values
        foreach ($this->cart_items as $item) {
            if (isset($item['cart_key'])) {
                $this->quantity_values[$item['cart_key']] = $item['quantity'] ?? 1;
            }
        } */
    }

    public function hydrate()
    {
        \Log::info('CartPage hydrate: Called', [
            'current_cart_items' => count($this->cart_items),
            'session_count' => count(session('cart_items', []))
        ]);
    }

    public function dehydrate()
    {
        \Log::info('CartPage dehydrate: Called', [
            'current_cart_items' => count($this->cart_items)
        ]);
    }

    private function refreshCart()
    {
        $this->cart_items = CartManagement::getCartItemsFromCookie();
        
        // Clean up any items missing cart_key before processing
        $this->cart_items = array_filter($this->cart_items, function($item) {
            if (is_array($item)) {
                return isset($item['cart_key']);
            }
            if (is_object($item)) {
                return isset($item->cart_key);
            }
            return false;
        });

        $this->cart_items = array_values($this->cart_items); // Re-index array
        
        $this->grouped_cart = CartManagement::groupCartByVendor($this->cart_items);
        $this->updateSelectedSummary();
        $this->dispatch('cart-updated');
    }

    public function increaseQty($cart_key)
    {
        $currentQty = $this->quantity_values[$cart_key] ?? 1;
        $newQty = $currentQty + 1;
        $this->quantity_values[$cart_key] = $newQty;
        
        $this->updateQty($cart_key, $newQty);
    }

    public function decreaseQty($cart_key)
    {
        $currentQty = $this->quantity_values[$cart_key] ?? 1;
        $newQty = max(1, $currentQty - 1);
        $this->quantity_values[$cart_key] = $newQty;
        
        $this->updateQty($cart_key, $newQty);
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
        
        // Re-group the cart after removal
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

    public function updateQty($cart_key, $quantity = null)
    {
        // If quantity is not provided, use the value from quantity_values
        if ($quantity === null && isset($this->quantity_values[$cart_key])) {
            $quantity = $this->quantity_values[$cart_key];
        }
        if ($quantity < 1) $quantity = 1;
        
        $previous_items = $this->cart_items;
        $this->cart_items = CartManagement::updateQuantity($cart_key, $quantity);
        if (empty($this->cart_items)) {
            $this->cart_items = $previous_items;
            $this->grouped_cart = CartManagement::groupCartByVendor($this->cart_items);
            $this->updateSelectedSummary();
            $this->dispatch('cart-updated');
            return;
        }

        $hasUpdatedItem = false;
        foreach ($this->cart_items as $item) {
            if (($item['cart_key'] ?? null) === $cart_key) {
                $hasUpdatedItem = true;
                break;
            }
        }
        if (!$hasUpdatedItem) {
            $this->cart_items = $previous_items;
            $this->grouped_cart = CartManagement::groupCartByVendor($this->cart_items);
            $this->updateSelectedSummary();
            $this->dispatch('cart-updated');
            return;
        }
        
        // Update local state
        foreach ($this->cart_items as &$item) {
            if (($item['cart_key'] ?? null) === $cart_key) {
                $actualQty = $item['quantity'] ?? $quantity;
                $unitAmount = $item['unit_amount'] ?? ($item['base_price'] ?? 0);
                $item['quantity'] = $actualQty;
                $item['total_amount'] = $unitAmount * $actualQty;
                break;
            }
        }
        
        // Update grouped cart
        $this->grouped_cart = CartManagement::groupCartByVendor($this->cart_items);
        
        // Update selected summary
        $this->updateSelectedSummary();
        
        // Update quantity_values to ensure sync
        $this->quantity_values[$cart_key] = $actualQty ?? $quantity;
        $this->dispatch('cart-updated');
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
        $this->selected_ids = [];

        // Reset if no cart items
        if (empty($this->cart_items)) {
            $this->selected_items = [];
            $this->selected_vendor_totals = [];
            $this->selected_total = 0;
            $this->selected_currency = 'USD';
            return;
        }
        
        // Get all valid cart keys
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
                
                if (isset($item['vendor_product_id'])) {
                    $this->selected_ids[] = $item['vendor_product_id'];
                }
            }
        }

        // Update vendor checkboxes
        $this->updateVendorCheckboxes();
        
        $this->selected_total = $totalUSD;
        $this->selected_currency = 'USD';
    }

    public function removeSelectedItems()
    {
        if (empty($this->selected_items)) {
            return;
        }

        $cart_items = CartManagement::getCartItemsFromCookie();
        $remaining_items = array_values(array_filter($cart_items, function ($item) {
            $cartKey = $item['cart_key'] ?? null;
            return $cartKey && !in_array($cartKey, $this->selected_items, true);
        }));

        CartManagement::addCartItemsToCookie($remaining_items, true);

        $this->cart_items = $remaining_items;
        $this->grouped_cart = CartManagement::groupCartByVendor($this->cart_items);
        $this->selected_items = [];
        $this->selected_ids = [];
        $this->selected_vendor_totals = [];
        $this->selected_total = 0;
        $this->quantity_values = [];

        foreach ($this->cart_items as $item) {
            if (isset($item['cart_key'])) {
                $this->quantity_values[$item['cart_key']] = $item['quantity'] ?? 1;
            }
        }

        $this->showDeleteSelectedModal = false;
        $this->dispatch('cart-updated');
    }

    public function confirmDeleteSelected()
    {
        if (empty($this->selected_items)) {
            return;
        }

        $this->showDeleteSelectedModal = true;
    }

    public function cancelDeleteSelected()
    {
        $this->showDeleteSelectedModal = false;
    }

    public function clearCartItems()
    {
        CartManagement::addCartItemsToCookie([], true);

        $this->cart_items = [];
        $this->grouped_cart = [];
        $this->selected_items = [];
        $this->selected_ids = [];
        $this->selected_vendor_totals = [];
        $this->selected_total = 0;
        $this->quantity_values = [];

        $this->dispatch('cart-updated');
    }

    /**
     * Get checkout URL with selected items
     */
    public function getCheckoutUrl(): string
    {
        if (empty($this->selected_ids)) {
            return '#';
        }
        
        return '/checkout?selected=' . urlencode(implode(',', $this->selected_ids));
    }

    /**
     * Get variation display text WITH QUANTITY
     */
    public function getVariationText($item)
    {
        $text = '';
    
        if (!empty($item['variation_note'])) {
            $text = $item['variation_note'];
        }
        
        // For backward compatibility: also check selected_variations
        if (empty($text) && !empty($item['selected_variations'])) {
            $parts = [];
            foreach ($item['selected_variations'] as $attribute => $value) {
                // Skip any meta attributes
                if (in_array($attribute, ['note', 'custom_note'])) {
                    continue;
                }
                $parts[] = ucfirst($attribute) . ': ' . $value;
            }
            if (!empty($parts)) {
                $text = implode(', ', $parts);
            }
        }
        
        // Always show quantity separately, not in the variation text
        // The quantity is displayed in the template, not here
        return $text;
    }

    public function render()
    {
        return view('livewire.cart-page');
    }
}
