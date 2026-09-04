<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VendorProduct;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Currency;
use Illuminate\Http\Request;

class ApiProductsPageController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Get filters - ensure they're properly parsed
            $selectedCategories = $this->parseFilterParam($request->get('categories', []));
            $selectedBrands = $this->parseFilterParam($request->get('brands', []));
            $vendorId = $request->get('vendor_id');
            $featured = filter_var($request->get('featured', false), FILTER_VALIDATE_BOOLEAN);
            $onSale = filter_var($request->get('on_sale', false), FILTER_VALIDATE_BOOLEAN);
            $priceRange = $request->get('price_range', 0);
            $sort = $request->get('sort', 'latest');
            $search = $request->get('search');
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 12);

            \Log::info('Products Page API called', [
                'categories' => $selectedCategories,
                'brands' => $selectedBrands,
                'vendor_id' => $vendorId
            ]);

            // Build query
            $query = VendorProduct::with(['product', 'vendor.currency', 'variations'])
                ->whereHas('product', function($q) {
                    $q->where('is_active', 1);
                })
                ->whereHas('vendor', function($q) {
                    $q->where('is_active', true);
                });

            // SEARCH
            if (!empty($search)) {
                $query->whereHas('product', function($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%');
                });
            }

            // VENDOR FILTER
            if (!empty($vendorId)) {
                $query->where('vendor_id', $vendorId);
            }

            // CATEGORY FILTER
            if (!empty($selectedCategories) && is_array($selectedCategories)) {
                $query->whereHas('product', function($q) use ($selectedCategories) {
                    $q->whereIn('category_id', $selectedCategories);
                });
            }

            // BRAND FILTER
            if (!empty($selectedBrands) && is_array($selectedBrands)) {
                $query->whereHas('product', function($q) use ($selectedBrands) {
                    $q->whereIn('brand_id', $selectedBrands);
                });
            }

            // FEATURED FILTER
            if ($featured) {
                $query->whereHas('product', function($q) {
                    $q->where('is_featured', 1);
                });
            }

            // ON SALE FILTER
            if ($onSale) {
                $query->whereNotNull('sale_price')
                      ->where('sale_price', '>', 0)
                      ->whereColumn('sale_price', '<', 'price');
            }

            // PRICE FILTER
            if ($priceRange > 0) {
                $query->where('price', '<=', $priceRange);
            }

            // SORT
            switch ($sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'popular':
                    // You might need a different logic for popular
                    $query->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->latest();
            }

            \Log::info('SQL Query', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            // Paginate
            $vendorProducts = $query->paginate($perPage, ['*'], 'page', $page);

            \Log::info('Found ' . $vendorProducts->total() . ' products');

            // Transform products
            $transformedProducts = [];
            foreach ($vendorProducts as $vp) {
                try {
                    $transformedProducts[] = $this->transformProduct($vp);
                } catch (\Exception $e) {
                    \Log::error('Failed to transform product ' . $vp->id . ': ' . $e->getMessage());
                    // Skip this product but continue
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $transformedProducts,
                    'pagination' => [
                        'current_page' => $vendorProducts->currentPage(),
                        'last_page' => $vendorProducts->lastPage(),
                        'per_page' => $vendorProducts->perPage(),
                        'total' => $vendorProducts->total(),
                        'from' => $vendorProducts->firstItem(),
                        'to' => $vendorProducts->lastItem(),
                    ],
                    'filters' => [
                        'categories' => Category::where('is_active', 1)->get(['id', 'name']),
                        'brands' => Brand::where('is_active', 1)->get(['id', 'name']),
                        'price_range' => (float) $priceRange,
                        'sort' => $sort,
                        'currency' => $request->header('Currency', 'USD'),
                    ],
                ],
                'message' => 'Products retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('🔴 Products Page API Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function parseFilterParam($param)
    {
        if (empty($param)) {
            return [];
        }
        
        if (is_array($param)) {
            return $param;
        }
        
        if (is_string($param)) {
            // Try to parse as JSON
            $decoded = json_decode($param, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            
            // Try to parse as comma-separated values
            if (strpos($param, ',') !== false) {
                return explode(',', $param);
            }
            
            // Single value
            return [$param];
        }
        
        return [];
    }

    private function transformProduct($vp)
    {
        $product = $vp->product;
        $vendor = $vp->vendor;
        
        if (!$product || !$vendor) {
            throw new \Exception('Product or vendor not found');
        }
        
        // Safely get vendor currency
        $vendorCurrency = $vendor->currency->code ?? 'USD';
        
        // Check if product has variations
        $hasVariations = $vp->has_variations && $vp->variations && $vp->variations->count() > 0;
        
        // Calculate price display
        $displayPrice = 0;
        $priceRange = false;
        $minPrice = 0;
        $maxPrice = 0;
        $totalStock = 0;
        $inStock = false;
        $salePrice = null;
        $variationsCount = 0;
        
        if ($hasVariations && $vp->variations) {
            $variationsCount = $vp->variations->count();
            $prices = $vp->variations->pluck('price')->filter()->toArray();
            
            if (!empty($prices)) {
                $minPrice = min($prices);
                $maxPrice = max($prices);
                $displayPrice = $minPrice;
                $priceRange = $minPrice != $maxPrice;
            }
            
            $totalStock = $vp->variations->sum('stock');
            $inStock = $totalStock > 0;
        } else {
            $displayPrice = $vp->sale_price ?? $vp->price ?? 0;
            $totalStock = $vp->stock ?? 0;
            $inStock = $totalStock > 0;
            
            if ($vp->sale_price) {
                $salePrice = $vp->sale_price;
            }
        }

        // Get image safely
        $image = null;
        if ($product && !empty($product->images) && is_array($product->images)) {
            $image = $product->images[0] ?? null;
        }

        return [
            'id' => $vp->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => $image ? url('uploads/' . $image) : null,
            'images' => array_map(function($img) {
                return url('uploads/' . $img);
            }, $product->images ?? []),
            
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->store_name,
            'vendor_slug' => $vendor->slug,
            'vendor_currency' => $vendorCurrency,
            
            'stock' => $totalStock,
            'in_stock' => $inStock,
            'currency' => $vendorCurrency,
            'display_price' => (float) $displayPrice,
            'min_price' => (float) $minPrice,
            'max_price' => (float) $maxPrice,
            'has_price_range' => $priceRange,
            'sale_price' => $salePrice ? (float) $salePrice : null,
            'original_price' => $hasVariations ? null : (float) ($vp->price ?? 0),
            'has_variations' => $hasVariations,
            'variations_count' => $variationsCount,
            'display_text' => $this->formatDisplayText($hasVariations, $priceRange, $minPrice, $maxPrice, $displayPrice, $salePrice, $vendorCurrency),
            'original_text' => $this->formatOriginalText($hasVariations, $variationsCount, $salePrice, $vp->price ?? 0, $vendorCurrency),
            'stock_text' => $hasVariations ? ($totalStock . ' pcs total') : ($totalStock . ' pcs'),
            'vendor_stock_text' => '🏬 ' . $vendor->store_name . ' – ' . ($inStock 
                ? ($hasVariations ? $totalStock . ' pcs total' : $totalStock . ' pcs')
                : 'Rupture'),
        ];
    }

    private function formatDisplayText($hasVariations, $priceRange, $minPrice, $maxPrice, $displayPrice, $salePrice, $currency)
    {
        if ($hasVariations) {
            if ($priceRange) {
                return number_format($minPrice, 2) . ' - ' . number_format($maxPrice, 2) . ' ' . $currency;
            }
            return number_format($minPrice, 2) . ' ' . $currency;
        }
        
        if ($salePrice && $salePrice < $displayPrice) {
            return number_format($salePrice, 2) . ' ' . $currency;
        }
        
        return number_format($displayPrice, 2) . ' ' . $currency;
    }

    private function formatOriginalText($hasVariations, $variationsCount, $salePrice, $originalPrice, $currency)
    {
        if ($hasVariations) {
            return $variationsCount . ' variante(s)';
        }
        
        if ($salePrice && $salePrice < $originalPrice) {
            return number_format($originalPrice, 2) . ' ' . $currency;
        }
        
        return null;
    }
}