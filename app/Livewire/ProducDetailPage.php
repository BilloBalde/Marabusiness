<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use App\Models\Product;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;

class ProducDetailPage extends Component
{
    #[Title('Product Detail Page - ECPG SA')]

    public $slug;
    public $quantity = 1;
    public $product;
    public $title;

    public function mount($slug)
    {
        $this->product = Product::where('slug', $slug)->firstOrFail();
        $this->title = $this->product->name . " - ByteWebster";
        $this->slug = $slug;
    }

    public function increaseQty()
    {
        $this->quantity++;
    }

    public function decreaseQty()
    {
        if ($this->quantity > 1)
        {
            $this->quantity--;
        }
    }

    public function addToCart($product_id)
    {
        $total_count = CartManagement::addItemToCartWithQty($product_id, $this->quantity);

        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        LivewireAlert::title('Produit Ajouté')
            ->text('Le produit a été ajouté à votre panier')
            ->success()
            ->show();

    }
    public function render()
    {
        return view('livewire.produc-detail-page', [
            'product' => \App\Models\Product::where('slug', $this->slug)->firstOrFail(),
            'categories' => \App\Models\Category::where('is_active', 1)->get(),
            'brands' => \App\Models\Brand::where('is_active', 1)->get(),
        ]);
    }
}
