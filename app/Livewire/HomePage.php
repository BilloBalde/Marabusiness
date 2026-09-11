<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Currency;
use App\Models\Category;
use App\Models\Vendor;
use App\Models\Service;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Helpers\WishlistManagement;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use App\Support\Money;

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

        // This block said "force a re-render" and did the exact opposite.
        // skipRender() tells Livewire not to render this request at all, and the
        // render() called after it returns a View that Livewire then discards —
        // so switching currency updated the component's state and left every
        // price on the screen exactly as it was. Livewire re-renders after a
        // public method by default; the correct code here is no code.
        $this->currencyChanged = !$this->currencyChanged;
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

        // The second step multiplied where it had to divide.
        //
        // rate_to_usd is what one unit of a currency is worth in dollars, so
        // going *into* dollars multiplies and coming back out divides. Writing
        // `$usd * $this->currencyRate` did the first step twice: a $100 product
        // shown to a shopper who picked GNF (rate 0.00012) came out as 0.012 GNF
        // instead of 833,333 — wrong by a factor of 69 million, and wrong in the
        // direction that makes everything look free.
        //
        // Invisible by default, because the default currency is USD at a rate of
        // 1 and multiplying by 1 hides it. It appears the moment anyone uses the
        // currency switcher in the navbar.
        return Money::convert((float) $price, (float) ($vendorRate ?: 1), (float) $this->currencyRate);
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

        // Real ratings and sales figures for the product cards.
        //
        // The view generated these with rand(): a star rating between 4.0 and 4.9
        // and a "vendus" count between 50 and 8000, recomputed on every render, so
        // the same product showed a different rating and a different sales figure
        // on each reload. They were presented to customers as fact — a product
        // with no reviews and no sales still displayed 4.7 stars and "3,412
        // vendus". Both now come from the tables that actually hold them, and the
        // view hides each one when there is nothing real to show.
        //
        // Two grouped queries for the whole page, in the spirit of the vendor
        // aggregates below, rather than two per card.
        $productIds = $featuredProducts->pluck('id')->merge($saleProducts->pluck('id'))->unique();
        $vendorProductIds = $featuredProducts->pluck('vendor_product_id')
            ->merge($saleProducts->pluck('vendor_product_id'))
            ->filter()
            ->unique();

        $soldCounts = DB::table('order_items')
            ->selectRaw('product_id, SUM(quantity) as sold')
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->pluck('sold', 'product_id');

        $productRatings = DB::table('vendor_product_reviews')
            ->selectRaw('vendor_product_id, AVG(rating) as avg_rating, COUNT(*) as reviews_count')
            ->whereIn('vendor_product_id', $vendorProductIds)
            ->groupBy('vendor_product_id')
            ->get()
            ->keyBy('vendor_product_id');

        $attachRealFigures = function ($product) use ($soldCounts, $productRatings) {
            $rating = $productRatings->get($product->vendor_product_id);
            $product->rating = $rating ? round((float) $rating->avg_rating, 1) : null;
            $product->reviews_count = $rating ? (int) $rating->reviews_count : 0;
            $product->sold_count = (int) ($soldCounts[$product->id] ?? 0);

            return $product;
        };

        $featuredProducts = $featuredProducts->map($attachRealFigures);
        $saleProducts = $saleProducts->map($attachRealFigures);

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
                // The card printed "{{ $vendor->products_count ?? rand(20, 200) }}
                // products", and products_count was never loaded — so every vendor
                // advertised a catalogue size between 20 and 200 that was drawn
                // fresh on each page load. Counting it here makes the number true.
                'products as products_count',
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
