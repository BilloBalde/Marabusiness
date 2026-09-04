<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $currencyCode = $request->header('Currency', 'USD');
        $currency = Currency::where('code', $currencyCode)->first();
        $currencyRate = $currency ? $currency->rate_to_usd : 1;

        // Featured products
        $featuredProducts = Product::with(['vendors.currency'])
            ->where('is_featured', 1)
            ->where('is_active', 1)
            ->get()
            ->map(fn($product) => $this->mapProductForApi($product, $currencyRate, $currencyCode))
            ->filter(fn($p) => $p['has_vendor']);

        // Sale products
        $saleProducts = Product::with(['vendors.currency'])
            ->where('on_sale', 1)
            ->where('is_active', 1)
            ->get()
            ->map(fn($product) => $this->mapProductForApi($product, $currencyRate, $currencyCode))
            ->filter(fn($p) => $p['has_vendor']);

        // Categories
        $categories = Category::where('is_active', 1)
            ->get(['id', 'name', 'slug', 'image']);

        // Vendors
        $vendors = Vendor::where('is_active', 1)
            ->with(['currency'])
            ->orderBy('created_at', 'desc')
            ->get([
                'id', 
                'store_name', 
                'slug', 
                'logo', 
                'banner',
                'description', 
                'currency', 
                'rating', 
                'is_verified',
                'is_featured', 
                'created_at'
            ])
            ->map(function($vendor) {
                return [
                    'id' => $vendor->id,
                    'store_name' => $vendor->store_name,
                    'slug' => $vendor->slug,
                    'logo' => $vendor->logo,
                    'banner' => $vendor->banner,
                    'description' => $vendor->description,
                    'currency' => $vendor->currency,
                    'rating' => $vendor->rating ?? 4.5,
                    'reviews_count' => $vendor->reviews_count ?? 0,
                    'followers_count' => $vendor->followers_count ?? 0,
                    'products_count' => $vendor->products_count ?? 0,
                    'is_verified' => $vendor->is_verified ?? false,
                    'is_featured' => $vendor->is_featured ?? false,
                    'created_at' => $vendor->created_at,
                ];
            });

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

        // Add this debug code
        if ($product->images && count($product->images) > 0) {
            $imagePath = public_path('uploads/' . $product->images[0]);
            if (file_exists($imagePath)) {
                $fileSize = filesize($imagePath);
                $mimeType = mime_content_type($imagePath);
                \Log::info("Product image: {$product->id} - Size: $fileSize, MIME: $mimeType");
            } else {
                \Log::info("Product image not found: {$product->images[0]}");
            }
        }

        $uniqueVendor = $product->vendors
            ->groupBy('id')
            ->map(fn($g) => $g->first())
            ->sortBy(fn($v) => $v->pivot->price)
            ->first();

        if (!$uniqueVendor) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'images' => $product->images ?? [],
                'has_vendor' => false,
            ];
        }

        $vendorRate = $uniqueVendor->currency->rate_to_usd ?? 1;
        
        // Get vendor_product_id
        $vp = VendorProduct::where('product_id', $product->id)
            ->where('vendor_id', $uniqueVendor->id)
            ->first();

        // Convert prices
        $basePriceUSD = $uniqueVendor->pivot->price * $vendorRate;
        $displayPrice = $basePriceUSD * $currencyRate;
        
        $salePrice = null;
        $discount = null;
        
        if ($uniqueVendor->pivot->sale_price) {
            $salePriceUSD = $uniqueVendor->pivot->sale_price * $vendorRate;
            $salePrice = $salePriceUSD * $currencyRate;
            
            if ($basePriceUSD > 0) {
                $discount = round(100 - ($salePriceUSD / $basePriceUSD * 100));
            }
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'images' => $product->images ?? [],
            'vendor_id' => $uniqueVendor->id,
            'vendor_product_id' => $vp ? $vp->id : null,
            'vendor_name' => $uniqueVendor->store_name,
            'vendor_slug' => $uniqueVendor->slug,
            'stock' => $uniqueVendor->pivot->stock ?? 0,
            'currency' => $uniqueVendor->currency->code ?? 'USD',
            'display_price' => (float) $uniqueVendor->pivot->price,
            'original_price' => (float) $uniqueVendor->pivot->price,
            'sale_price' => (float) $uniqueVendor->pivot->price,
            'sale_end' => $uniqueVendor->pivot->sale_end,
            'discount' => $discount,
            'has_vendor' => true,
        ];
    }
}