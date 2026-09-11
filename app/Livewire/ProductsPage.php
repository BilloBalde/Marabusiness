<?php

namespace App\Livewire;

use App\Helpers\WishlistManagement;
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
use App\Support\Money;

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

    public function addToWishlist($vendor_product_id)
    {
        if (!$vendor_product_id) {
            $this->dispatch('show-toast', 
                message: 'This product is not available.',
                type: 'error'
            );
            return;
        }
        
        WishlistManagement::addItem($vendor_product_id, null, []);
        $this->dispatch('wishlist-updated', total_count: WishlistManagement::getCount());
        $this->dispatch('show-toast', 
            message: 'Added to wishlist.',
            type: 'success'
        );
    }

    /**
     * Convert vendor price → USD → selected currency.
     *
     * Nothing on this page calls it, which is why the catalogue lists raw vendor
     * prices and ignores the currency switcher entirely while the home page
     * converts. Kept rather than deleted because the listing arguably should
     * honour the switcher — but corrected first: this carried the same inverted
     * second step as HomePage's copy, multiplying by rate_to_usd where it had to
     * divide, so wiring it up as it stood would have shown a $100 product as
     * 0.012 GNF.
     */
    private function convertPrice($price, $vendorRate)
    {
        if (!$price || $price <= 0) return 0;

        return Money::convert((float) $price, (float) ($vendorRate ?: 1), (float) $this->currencyRate);
    }

    public function render()
    {
        // 🔥🔥 THE REAL SOURCE OF TRUTH
        // One row per catalogue product, at its best price: this page has no vendor
        // filter, so a product carried by three shops used to appear three times.
        $query = VendorProduct::with(['product', 'vendor.currency'])
            ->whereHas('product', fn($q) => $q->where('is_active', 1))
            ->cheapestPerProduct()
            // vendor_product.price is in the vendor's own currency, and the price
            // filter and the price sort below both compared it as a bare number.
            // Sorted "cheapest first", the catalogue's actual cheapest item
            // (50,000 GNF = $6) came fourth, behind an 11,000 CNY phone ($1,540),
            // because 50,000 > 11,000. The filter had the same fault plus a label
            // that read "USD" over a threshold compared against GNF.
            //
            // Joining the vendor's currency gives a comparable figure. leftJoin
            // with a COALESCE fallback so a vendor whose currency row ever went
            // missing drops out of the ordering, not out of the catalogue.
            ->select('vendor_product.*')
            ->leftJoin('vendors', 'vendors.id', '=', 'vendor_product.vendor_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'vendors.currency_id');

        // The price actually charged, expressed in USD — sale price when there is
        // one, list price otherwise, matching cheapestPerProduct()'s definition.
        $priceUsd = 'COALESCE(NULLIF(vendor_product.sale_price, 0), vendor_product.price)'
            . ' * COALESCE(currencies.rate_to_usd, 1)';

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
            $query->whereNotNull('vendor_product.sale_price');
        }

        /** PRICE FILTER */
        if ($this->price_range > 0) {
            $query->whereRaw("({$priceUsd}) <= ?", [$this->price_range]);
        }

        /** SORT */
        // Qualified column names throughout: vendor_product, vendors and
        // currencies all carry created_at, so an unqualified latest() is
        // ambiguous once those tables are joined.
        if ($this->sort === 'price') {
            $query->orderByRaw("({$priceUsd}) ASC");
        } else {
            $query->latest('vendor_product.created_at');
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
