<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Vendor;
use App\Support\Money;
use App\Support\VendorPresenter;
use App\Support\ProductPricing;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $currencyCode = $request->header('Currency', 'USD');
        $currency = Currency::where('code', $currencyCode)->first();
        $currencyRate = $currency ? $currency->rate_to_usd : 1;

        // Featured products
        $featuredProducts = Product::with($this->offerRelations())
            ->where('is_featured', 1)
            ->where('is_active', 1)
            ->get()
            ->map(fn($product) => $this->mapProductForApi($product, $currencyRate, $currencyCode))
            ->filter(fn($p) => $p['has_vendor']);

        // Sale products
        $saleProducts = Product::with($this->offerRelations())
            ->where('on_sale', 1)
            ->where('is_active', 1)
            ->get()
            ->map(fn($product) => $this->mapProductForApi($product, $currencyRate, $currencyCode))
            ->filter(fn($p) => $p['has_vendor']);

        // Categories
        $categories = Category::where('is_active', 1)
            ->get(['id', 'name', 'slug', 'image']);

        // Vendors
        // Selecting logo / banner / rating / is_verified / is_featured as columns was
        // silently wrong: none of them exist on `vendors`. SQLite returned them as string
        // literals, so every logo came back null and every rating fell to a hardcoded 4.5,
        // and the same query raises "Unknown column" on MySQL. VendorPresenter reads the
        // real logo_path and the computed rating, and serves /vendors identically.
        $vendors = VendorPresenter::eagerLoad(Vendor::where('is_active', 1))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Vendor $vendor) => VendorPresenter::present($vendor));

        // Services
        $services = Service::all()->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'slug' => $s->slug,
            'icon' => $s->icon,
            'short_description' => $s->short_description,
        ]);

        // Banners
        $banners = SiteSetting::whereIn('key', [
            'home-page-banner-1',
            'home-page-banner-2',
            'home-page-banner-3',
        ])->get()->mapWithKeys(fn($item) => [$item['key'] => $item['value']]);

        return response()->json([
            'currency' => [
                'code' => $currencyCode,
                'rate' => $currencyRate
            ],
            'featured_products' => $featuredProducts->values(),
            'sale_products' => $saleProducts->values(),
            'categories' => $categories,
            'vendors' => $vendors->values(),
            'services' => $services,
            'banners' => $banners,
        ]);
    }

    private function mapProductForApi($product, $currencyRate, $currencyCode)
    {
        if (!$product->vendors || $product->vendors->isEmpty()) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'images' => $product->images ?? [],
                'has_vendor' => false,
            ];
        }

        // Cheapest active offer wins: one product is shown once, at its best price,
        // instead of once per shop that stocks it.
        $offer = $product->vendorProducts
            ->filter(fn ($vp) => $vp->is_active && $vp->vendor && $vp->vendor->is_active)
            ->sortBy(fn ($vp) => ProductPricing::for($vp, '')['display_price'])
            ->first();

        if (! $offer) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'images' => $product->images ?? [],
                'has_vendor' => false,
            ];
        }

        $vendor         = $offer->vendor;
        $vendorCurrency = $vendor->currency->code ?? 'USD';
        $vendorRate     = $vendor->currency->rate_to_usd ?? 1;

        $pricing = ProductPricing::for($offer, $vendorCurrency);

        // Vendor currency -> USD -> the currency the client asked for. The old code
        // computed this and then returned the raw pivot price anyway, which is why
        // display_price, original_price and sale_price were all the same number and no
        // discount ever appeared on the home feed.
        // rate_to_usd converts *into* USD, so the target currency divides rather than
        // multiplies: 1416 USD / 0.00012 = 11.8M GNF, not 0.17. This endpoint got it
        // right while HomePage's Livewire copy multiplied both ways; App\Support\Money
        // holds the rule for both now.
        $toRequested = fn (?float $amount) => $amount === null
            ? null
            : round(Money::convert($amount, $vendorRate, $currencyRate), 2);

        $displayPrice  = $toRequested($pricing['display_price']);
        $originalPrice = $toRequested($pricing['original_price']);
        $salePrice     = $toRequested($pricing['sale_price']);

        $discount = null;
        if ($salePrice !== null && $originalPrice > 0 && $salePrice < $originalPrice) {
            $discount = (int) round(100 - ($salePrice / $originalPrice * 100));
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'images' => $product->images ?? [],
            'vendor_id' => $vendor->id,
            'vendor_product_id' => $offer->id,
            'vendor_name' => $vendor->store_name,
            'vendor_slug' => $vendor->slug,
            'stock' => $pricing['total_stock'],
            'currency' => $currencyCode,
            'display_price' => $displayPrice,
            'original_price' => $originalPrice,
            'sale_price' => $salePrice,
            'sale_end' => $offer->sale_end,
            'discount' => $discount,
            'has_variations' => $pricing['has_variations'],
            'variations_count' => $pricing['variations_count'],
            'min_price' => $toRequested($pricing['min_price']),
            'max_price' => $toRequested($pricing['max_price']),
            'has_price_range' => $pricing['has_price_range'],
            // Rebuilt from the converted amounts: ProductPricing formats in the vendor's
            // currency, and this feed answers in the currency the client asked for.
            'display_text' => ProductPricing::displayText(
                $pricing['has_variations'], $pricing['has_price_range'],
                (float) $toRequested($pricing['min_price']), (float) $toRequested($pricing['max_price']),
                (float) $displayPrice, $salePrice, $currencyCode
            ),
            'original_text' => ProductPricing::originalText(
                $pricing['has_variations'], $pricing['variations_count'],
                $salePrice, (float) $originalPrice, $currencyCode
            ),
            'has_vendor' => true,
        ];
    }

    /**
     * Everything mapProductForApi() reads, so the feed costs a fixed number of queries.
     */
    private function offerRelations(): array
    {
        return ['vendorProducts.vendor.currency', 'vendorProducts.variations'];
    }
}
