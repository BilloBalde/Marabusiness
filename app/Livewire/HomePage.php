<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Currency;
use App\Models\Category;
use App\Models\Vendor;
use App\Models\Service;
use App\Models\SiteSetting;
use Livewire\Component;
use App\Helpers\WishlistManagement;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;

class HomePage extends Component
{
    #[Title('Home Page - MARA BUSINESS')]

    public $currencyCode;     // selected currency: USD / EUR / RMB / FG
    public $currencyRate = 1; // selected currency → USD conversion rate
    public $currencyChanged = false; // Flag to force re-render

    public function mount()
    {
        // Load selected currency from session
        $this->currencyCode = session('currency_code', 'USD');

        // Load rate for selected currency
        $this->currencyRate = Currency::where('code', $this->currencyCode)
            ->value('rate_to_usd') ?? 1;
            
    }

    /**
     * When Navbar triggers a currency change event
     */
    #[On('currency-changed')]
    public function updateCurrency(string $code)
    {
        \Log::info('HOMEPAGE: Currency change event received', [
            'old_currency' => $this->currencyCode,
            'new_currency' => $code,
            'old_rate' => $this->currencyRate,
        ]);
        // Clear the old values
        $this->reset(['currencyCode', 'currencyRate']);
        
        $this->currencyCode = $code;
        session(['currency_code' => $code]);

        // Refresh selected currency rate
        $this->currencyRate = Currency::where('code', $code)
            ->value('rate_to_usd') ?? 1;
            
        \Log::info('HOMEPAGE: Currency updated', [
            'new_rate' => $this->currencyRate,
        ]);

        // Force Livewire to re-render the component
        $this->currencyChanged = !$this->currencyChanged;
        // Force a re-render
        // This forces Livewire to see the component as "dirty" and re-render
        $this->skipRender(); // Skip current render
        $this->render(); // Force new render
    }

    /**
     * Convert vendor PP prices:
     * VENDOR_CURRENCY → USD → SELECTED_CURRENCY
     */
    private function convertPrice($price, $vendorRate)
    {
        if (!$price || $price <= 0) {
            return 0;
        }

        // Convert vendor price → USD
        $usd = $price * ($vendorRate ?: 1);

        // Convert USD → selected currency
        return $usd * $this->currencyRate;
    }

    /**
     * Map products for homepage layout
     */
    private function mapProductForHomepage(Product $product)
    {
        // Check if product has vendors
        if (!$product->vendors || $product->vendors->isEmpty()) {
            return (object)[
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'images' => $product->images ?? [],
                'vendor_id' => null,
                'vendor_product_id' => null,
                'vendor_name' => null,
                'stock' => 0,
                'vendor_currency' => $this->currencyCode,
                'display_price' => 0,
                'original_price' => 0,
                'sale_price' => null,
                'sale_end' => null,
                'discount' => null,
                'has_vendor' => false, // Add flag to check if product has vendor
            ];
        }

        // Remove duplicated vendors (variations)
        $uniqueVendor = $product->vendors
            ->groupBy('id')
            ->map(fn($g) => $g->first())
            ->sortBy(fn($v) => $v->pivot->price)
            ->first();

        if (!$uniqueVendor) {
            return (object)[
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'images' => $product->images ?? [],
                'vendor_id' => null,
                'vendor_product_id' => null,
                'vendor_name' => null,
                'stock' => 0,
                'vendor_currency' => $this->currencyCode,
                'display_price' => 0,
                'original_price' => 0,
                'sale_price' => null,
                'sale_end' => null,
                'discount' => null,
                'has_vendor' => false,
            ];
        }

        // $uniqueVendor is one of $product->vendors, eager-loaded with the
        // product — the pivot already carries this exact vendor_product row's id
        // (see Product::vendors()'s withPivot()); no need to re-query it.
        $vendor_product_id = $uniqueVendor->pivot->id ?? null;

        // Vendor currency → USD rate
        $vendorRate = $uniqueVendor->currency->rate_to_usd ?? 1;

        // Convert prices
        $base = $this->convertPrice($uniqueVendor->pivot->price, $vendorRate);

        $sale = $uniqueVendor->pivot->sale_price
            ? $this->convertPrice($uniqueVendor->pivot->sale_price, $vendorRate)
            : null;
        
        $sale_end = $uniqueVendor->pivot->sale_end;

        return (object)[
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'images' => $product->images ?? [],
            'vendor_id' => $uniqueVendor->id,
            'vendor_product_id' => $vendor_product_id,
            'vendor_name' => $uniqueVendor->store_name,
            'vendor_slug' => $uniqueVendor->slug ?? null,
            'stock' => $uniqueVendor->pivot->stock ?? 0,
            'vendor_currency' => $this->currencyCode,
            'display_price' => $sale ?? $base,
            'original_price' => $base,
            'sale_price' => $sale,
            'sale_end' => $sale_end,
            'discount' => $sale && $base > 0
                ? round(100 - ($sale / $base * 100))
                : null,
            'has_vendor' => true, // Add flag to check if product has vendor
        ];
    }

    /**
     * Add to cart
     */
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

    public function render()
    {
        // Get featured products
        $featuredProducts = Product::with(['vendors.currency', 'vendors.translations'])
            ->where('is_featured', 1)
            ->where('is_active', 1)
            ->get()
            ->map(fn($p) => $this->mapProductForHomepage($p))
            ->filter(fn($p) => $p->has_vendor);

        // Get sale products
        $saleProducts = Product::with(['vendors.currency', 'vendors.translations'])
            ->where('on_sale', 1)
            ->where('is_active', 1)
            ->get()
            ->map(fn($p) => $this->mapProductForHomepage($p))
            ->filter(fn($p) => $p->has_vendor);

        // Get categories with their first product (if any)
        //
        // 'translations' eager-loaded for $category->name below — otherwise
        // HasTranslations::getTranslation() re-queries category_translations
        // itself, once per field access, since it only checks whether the
        // relation is already loaded and re-fetches fresh every time it isn't.
        $categories = Category::with(['products' => function($query) {
            $query->where('is_active', 1)
                  ->orderBy('created_at', 'desc')
                  ->limit(1);
        }, 'translations'])->where('is_active', 1)->get();

        // Get active vendors.
        //
        // This page used to run 106 queries (~2.3s) for 7 products and 8 vendors:
        // 28 for vendor_translations, 10 each for the rating average and the
        // follower count, all repeated per vendor per field access because none
        // of it was eager-loaded. 'translations' fixes store_name/description the
        // same way as $categories above; withAvg/withCount compute the rating and
        // follower count in this one query instead of two more per vendor.
        $vendors = Vendor::with('translations')
            ->withAvg('approvedVendorReviews as vendor_rating', 'rating')
            ->withCount([
                'approvedVendorReviews as vendor_reviews_count',
                'followers as followers_count',
            ])
            ->where('is_active', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get services — 'translations' eager-loaded for the same reason as
        // categories and vendors above (name/description via HasTranslations).
        $services = Service::with('translations')->get();
        // Get banners
        $banners = SiteSetting::whereIn('key', [
            'home-page-banner-1',
            'home-page-banner-2',
            'home-page-banner-3',
        ])->get();


        //dd($banners);

        return view('livewire.home-page', [
            'featuredProducts' => $featuredProducts,
            'saleProducts' => $saleProducts,
            'categories' => $categories,
            'vendors' => $vendors,
            'services' => $services,
            'banners' => $banners,
        ]);
    }
}
