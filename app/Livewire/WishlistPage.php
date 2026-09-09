<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Helpers\WishlistManagement;
use App\Helpers\CartManagement;
use Illuminate\Support\Facades\Auth;

class WishlistPage extends Component
{
    #[Title('Wishlist - MARA BUSINESS')]

    public $wishlist_items = [];
    public $selected_items = [];

    public function mount()
    {
        $this->refreshWishlist();
    }

    private function refreshWishlist()
    {
        $this->wishlist_items = WishlistManagement::getWishlistDetails();
        $validKeys = array_map(fn($item) => $item['wishlist_key'], $this->wishlist_items);
        $this->selected_items = array_values(array_intersect($this->selected_items, $validKeys));
    }

    public function toggleItem($wishlist_key)
    {
        $key = array_search($wishlist_key, $this->selected_items, true);
        if ($key !== false) {
            unset($this->selected_items[$key]);
        } else {
            $this->selected_items[] = $wishlist_key;
        }
        $this->selected_items = array_values($this->selected_items);
    }

    public function removeItem($wishlist_key)
    {
        WishlistManagement::removeItem($wishlist_key);
        $this->selected_items = array_values(array_filter(
            $this->selected_items,
            fn($key) => $key !== $wishlist_key
        ));
        $this->refreshWishlist();
        $this->dispatch('wishlist-updated', total_count: WishlistManagement::getCount());
    }

    public function clearWishlist()
    {
        WishlistManagement::clear();
        $this->selected_items = [];
        $this->refreshWishlist();
        $this->dispatch('wishlist-updated', total_count: 0);
    }

    public function addSelectedToCart()
    {
        if (empty($this->selected_items)) {
            return;
        }

        if (!Auth::check()) {
            $this->dispatch('show-toast',
                message: 'Please login to add items to cart.',
                type: 'warning'
            );
            return $this->redirectRoute('login');
        }

        $selected = array_filter($this->wishlist_items, function ($item) {
            return in_array($item['wishlist_key'], $this->selected_items, true);
        });

        foreach ($selected as $item) {
            CartManagement::addItemToCart(
                vendor_product_id: $item['vendor_product_id'],
                quantity: 1,
                variation_id: $item['variation_id'] ?? null,
                selectedAttributes: $item['selected_variations'] ?? [],
                custom_note: ''
            );
        }

        foreach ($this->selected_items as $wishlistKey) {
            WishlistManagement::removeItem($wishlistKey);
        }
        $this->selected_items = [];
        $this->refreshWishlist();
        $this->dispatch('wishlist-updated', total_count: WishlistManagement::getCount());

        $this->dispatch('cart-updated', total_count: CartManagement::getCartCount());
        $this->dispatch('show-toast',
            message: 'Selected items added to cart.',
            type: 'success'
        );
    }

    public function getVariationText($item)
    {
        $text = '';
        if (!empty($item['selected_variations'])) {
            $parts = [];
            foreach ($item['selected_variations'] as $attribute => $value) {
                $parts[] = ucfirst($attribute) . ': ' . $value;
            }
            if (!empty($parts)) {
                $text = implode(', ', $parts);
            }
        }
        return $text;
    }

    public function render()
    {
        return view('livewire.wishlist-page');
    }
}
