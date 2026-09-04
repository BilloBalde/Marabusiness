<?php

namespace App\Livewire;

use Livewire\Component;
use App\Helpers\CartManagement;

class CartConflictModal extends Component
{
    public $show = false;
    public $message = '';
    public $existingCartKey = null;
    public $productName = null;

    protected $listeners = [
        'showCartConflictModal' => 'showModal',
        'cart-item-removed' => 'closeModal'
    ];

    public function showModal($data)
    {
        $this->message = $data['message'] ?? '';
        $this->existingCartKey = $data['existingCartKey'] ?? null;
        $this->productName = $data['productName'] ?? null;
        $this->show = true;
    }

    public function closeModal()
    {
        $this->reset(['show', 'message', 'existingCartKey', 'productName']);
    }

    public function goToCart()
    {
        $this->closeModal();
        return redirect()->to('/cart');
    }

    public function removeExistingItem()
    {
        if ($this->existingCartKey) {
            CartManagement::removeCartItem($this->existingCartKey);
            $this->dispatch('cart-updated');
            $this->dispatch('cart-item-removed');
            $this->dispatch('show-toast', 
                message: 'Item removed from cart successfully',
                type: 'success'
            );
        }
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.cart-conflict-modal');
    }
}