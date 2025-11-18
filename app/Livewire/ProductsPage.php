<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use App\Models\Product;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class ProductsPage extends Component
{
    use WithPagination;
    #[Title('Products Page - ECPG SA')]
    #[Url]
    public $selectedCategories = [];

    #[Url]
    public $selectedBrands = [];

    #[Url]
    public $featured;

    #[Url]
    public $on_sale;

    #[Url]
    public $price_range = 0;

    #[Url]
    public $sort = 'latest';

    // Method for adding the product in the cart

    public function addToCart($product_id)
    {
        $total_count = CartManagement::addItemToCart($product_id);

        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        LivewireAlert::title('Produit Ajouté')
            ->text('Le produit a été ajouté à votre panier')
            ->success()
            ->show();

    }
    public function render()
    {
        $productQuery = Product::query()
            ->where('is_active', 1)
            ->with(['category', 'brand'])
            ->orderBy('created_at', 'desc');

        if (!empty($this->selectedCategories)) {
            $productQuery->whereIn('category_id', $this->selectedCategories);
        }
        if (!empty($this->selectedBrands)) {
            $productQuery->whereIn('brand_id', $this->selectedBrands);
        }
        if ($this->featured) {
            $productQuery->where('is_featured', 1);
        }
        if ($this->on_sale) {
            $productQuery->where('on_sale', 1);
        }
        if ($this->price_range) {
            $productQuery->whereBetween('price', [0, $this->price_range]);
        }
        if ($this->sort == 'latest') {
            $productQuery->latest();
        }
        if ($this->sort == 'price') {
            $productQuery->orderBy('price', 'desc');
        }
        return view('livewire.products-page',[
            'products' => $productQuery->paginate(9),
            'categories' => \App\Models\Category::where('is_active', 1)->get(),
            'brands' => \App\Models\Brand::where('is_active', 1)->get(),
            'price_range' => $this->price_range,
        ]);
    }
}
