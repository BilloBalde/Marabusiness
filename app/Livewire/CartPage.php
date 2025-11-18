<?php

namespace App\Livewire;

use App\Livewire\Partials\Navbar;
use Livewire\Attributes\Title;
use Livewire\Component;

class CartPage extends Component
{
    #[Title('Cart Page - MARA BUSINESS')]

    public $cart_items = [];
    public $grand_total;

    public function mount()
    {
        $this->cart_items = \App\Helpers\CartManagement::getCartItemsFromCookie();
        $this->grand_total = \App\Helpers\CartManagement::calculateGrandTotal($this->cart_items);
    }

    public function removeItem($product_id){
        $this->cart_items = \App\Helpers\CartManagement::removeCartItems($product_id);
        $this->grand_total = \App\Helpers\CartManagement::calculateGrandTotal($this->cart_items);
        $this->dispatch('update-cart-count', total_count: count($this->cart_items))->to(Navbar::class);
    }

    public function decreaseQty($product_id){
        $this->cart_items = \App\Helpers\CartManagement::decrementQuantityToCartItem($product_id);
        $this->grand_total = \App\Helpers\CartManagement::calculateGrandTotal($this->cart_items);
        $this->dispatch('update-cart-count', total_count: count($this->cart_items))->to(Navbar::class);
    }

    public function increaseQty($product_id){
        $this->cart_items = \App\Helpers\CartManagement::incrementQuantityToCartItem($product_id);
        $this->grand_total = \App\Helpers\CartManagement::calculateGrandTotal($this->cart_items);
        $this->dispatch('update-cart-count', total_count: count($this->cart_items))->to(Navbar::class);
    }

    public function render()
    {
        return view('livewire.cart-page');
    }
}
