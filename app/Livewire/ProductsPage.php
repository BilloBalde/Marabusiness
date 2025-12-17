<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use App\Models\Product;
use App\Models\VendorProduct;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Currency;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class ProductsPage extends Component
{
    use WithPagination;

    #[Title('Products - MARA BUSINESS')]

    #[Url] public $selectedCategories = [];
    #[Url] public $selectedBrands = [];
    #[Url] public $featured = false;
    #[Url] public $on_sale = false;
    #[Url] public $price_range = 0;
    #[Url] public $sort = 'latest';
    #[Url] public $search;

    public $currencyCode;   // selected currency (from session)
    public $currencyRate = 1;

    public function mount()
    {
        // load selected currency
        $this->currencyCode = session('currency_code', 'USD');

        // load rate
        $this->currencyRate = Currency::where('code', $this->currencyCode)
            ->value('rate_to_usd') ?? 1;
    }

    /**
     * React when navbar changes currency
     */
    #[On('currency-changed')]
    public function updateCurrency(string $code)
    {
        $this->currencyCode = $code;
        session(['currency_code' => $code]);

        $this->currencyRate = Currency::where('code', $code)->value('rate_to_usd') ?? 1;

        $this->resetPage();
    }

    public function updating($field)
    {
        $this->resetPage();
    }

    public function addToCart($vendor_product_id)
    {
        // Add item to cart with empty variations (since it's from homepage)
        $total_count = \App\Helpers\CartManagement::addItemToCart(
            vendor_product_id: $vendor_product_id,
            quantity: 1,
            selectedVariations: [],
            custom_note: ''
        );

        $this->dispatch('cart-updated', total_count: $total_count)->to(Navbar::class);
        $this->dispatch('cart-added');
    }

    /**
     * Convert vendor price → USD → selected currency
     */
    private function convertPrice($price, $vendorRate)
    {
        if (!$price || $price <= 0) return 0;

        $usd = $price * ($vendorRate ?: 1);
        return $usd * $this->currencyRate;
    }

    public function render()
    {
        // 🔥🔥 THE REAL SOURCE OF TRUTH
        $query = VendorProduct::with(['product', 'vendor.currency'])
            ->whereHas('product', fn($q) => $q->where('is_active', 1));

        /** SEARCH */
        if ($this->search) {
            $query->whereHas('product', fn($p) =>
                $p->where('name', 'LIKE', '%' . $this->search . '%')
            );
        }

        /** CATEGORY */
        if (!empty($this->selectedCategories)) {
            $query->whereHas('product', fn($p) =>
                $p->whereIn('category_id', $this->selectedCategories)
            );
        }

        /** BRAND */
        if (!empty($this->selectedBrands)) {
            $query->whereHas('product', fn($p) =>
                $p->whereIn('brand_id', $this->selectedBrands)
            );
        }

        /** FEATURED */
        if ($this->featured) {
            $query->whereHas('product', fn($p) =>
                $p->where('is_featured', 1)
            );
        }

        /** ON SALE */
        if ($this->on_sale) {
            $query->whereNotNull('sale_price');
        }

        /** PRICE FILTER */
        if ($this->price_range > 0) {
            $query->where('price', '<=', $this->price_range);
        }

        /** SORT */
        if ($this->sort === 'price') {
            $query->orderBy('price', 'ASC');
        } else {
            $query->latest();
        }

        $vendorProducts = $query->paginate(12);
        //dd($vendorProducts);

        return view('livewire.products-page', [
            'vendorProducts' => $vendorProducts,
            'categories'     => Category::where('is_active', 1)->get(),
            'brands'         => Brand::where('is_active', 1)->get(),
            'price_range' => $this->price_range,
            'currencyCode'   => $this->currencyCode,
        ]);
    }
}
