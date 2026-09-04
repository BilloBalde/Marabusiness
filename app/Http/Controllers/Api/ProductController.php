<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\VendorProduct;
use App\Models\Currency;
use App\Models\VendorProductReview;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $currencyCode = $request->header('Currency', 'USD');
        $currency = Currency::where('code', $currencyCode)->first();
        $currencyRate = $currency ? $currency->rate_to_usd : 1;

        // Query VendorProduct directly instead of Product
        $query = VendorProduct::with(['product', 'vendor.currency'])
            ->whereHas('product', function($q) {
                $q->where('is_active', 1);
            })
            ->whereHas('vendor', function($q) {
                $q->where('is_active', true);
            });

        // Search by product name
        if ($request->has('search') && !empty($request->search)) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // Category filter
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Vendor filter
        if ($request->has('vendor_id') && !empty($request->vendor_id)) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Price range filters
        if ($request->has('min_price') && $request->min_price > 0) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price') && $request->max_price < 1000000) {
            $query->where('price', '<=', $request->max_price);
        }

        // Featured filter
        if ($request->has('featured') && $request->featured) {
            $query->whereHas('product', function($q) {
                $q->where('is_featured', 1);
            });
        }

        // On sale filter
        if ($request->has('on_sale') && $request->on_sale) {
            $query->whereNotNull('sale_price');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        switch ($sortBy) {
            case 'price':
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('vendor_product.created_at', 'desc');
                break;
            case 'popular':
            default:
                $query->orderBy('vendor_product.created_at', 'desc');
                break;
        }

        $perPage = $request->get('per_page', 15);
        $vendorProducts = $query->paginate($perPage);

        // Transform the vendor products to match your Product model structure
        $vendorProducts->getCollection()->transform(function ($vendorProduct) use ($currencyRate, $currencyCode) {
            $product = $vendorProduct->product;
            $vendor = $vendorProduct->vendor;
            
            $vendorRate = $vendor->currency->rate_to_usd ?? 1;
            
            // Convert prices
            $basePriceUSD = $vendorProduct->price * $vendorRate;
            $displayPrice = $basePriceUSD * $currencyRate;
            
            $salePrice = null;
            $discount = null;
            
            if ($vendorProduct->sale_price) {
                $salePriceUSD = $vendorProduct->sale_price * $vendorRate;
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
                'vendor_id' => $vendor->id,
                'vendor_product_id' => $vendorProduct->id,
                'vendor_name' => $vendor->store_name,
                'vendor_slug' => $vendor->slug,
                'vendor_rating' => $vendor->rating ?? 0,
                'stock' => $vendorProduct->stock ?? 0,
                'currency' => $currencyCode,
                'display_price' => $salePrice ?? $displayPrice,
                'original_price' => $displayPrice,
                'sale_price' => $salePrice,
                'sale_end' => $vendorProduct->sale_end,
                'discount' => $discount,
                'has_vendor' => true,
                'category' => $product->category?->name,
                'brand' => $product->brand?->name,
            ];
        });

        return response()->json($vendorProducts);
    }

    public function show($slug, $vendor_product_id, Request $request)
    {
        $token = $request->bearerToken();
        \Log::info('Bearer token present: ' . ($token ? 'Yes' : 'No'));
        
        if ($token) {
            \Log::info('Token length: ' . strlen($token));
            \Log::info('Token first 20 chars: ' . substr($token, 0, 20) . '...');
        }
        
        // DEBUG: Check if user is authenticated via any guard
        \Log::info('Auth check - via request: ' . ($request->user() ? 'Yes' : 'No'));
        \Log::info('Auth check - via auth(): ' . (auth()->user() ? 'Yes' : 'No'));
        \Log::info('Auth check - via auth(sanctum): ' . (auth('sanctum')->user() ? 'Yes' : 'No'));
        
        // If any guard found user, try to set it
        if (!$request->user() && auth('sanctum')->user()) {
            \Log::info('Setting user from sanctum guard');
            $request->setUserResolver(function() {
                return auth('sanctum')->user();
            });
        }
        // We still get the currency header but we won't convert prices
        $currencyCode = $request->header('Currency', 'USD');

        $product = Product::with(['category', 'brand'])
            ->where('slug', $slug)
            ->where('is_active', 1)
            ->firstOrFail();

        // FIXED: Eager load ALL relationships properly
        $vendorProduct = VendorProduct::with([
            'vendor.currency', 
            'variations.wholesaleTiers', // Load variations with their wholesale tiers
            'wholesaleTiers'
        ])->findOrFail($vendor_product_id);

        $mappedProduct = $this->mapProductDetail($product, $vendorProduct, $currencyCode);

        $similarProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', 1)
            ->with(['vendorProducts' => function($query) {
                $query->with(['vendor.currency', 'variations'])
                    ->orderBy('price');
            }])
            ->limit(8)
            ->get()
            ->map(function($similarProduct) {
                // Get the cheapest vendor product for this similar product
                $cheapestVendorProduct = $similarProduct->vendorProducts
                    ->filter(function($vp) {
                        return $vp->vendor && $vp->vendor->is_active;
                    })
                    ->sortBy('price')
                    ->first();
                
                if (!$cheapestVendorProduct) {
                    return null;
                }
                
                $hasVariations = $cheapestVendorProduct->has_variations && 
                                $cheapestVendorProduct->variations->count() > 0;
                $vendorCurrency = $cheapestVendorProduct->vendor->currency->code ?? 'USD';
                
                if ($hasVariations) {
                    $minPrice = $cheapestVendorProduct->variations->min('price');
                    $maxPrice = $cheapestVendorProduct->variations->max('price');
                    $priceDisplay = $minPrice == $maxPrice 
                        ? number_format($minPrice, 2)
                        : number_format($minPrice, 2) . ' - ' . number_format($maxPrice, 2);
                    $inStock = $cheapestVendorProduct->variations->sum('stock') > 0;
                } else {
                    $price = (float) ($cheapestVendorProduct->sale_price ?: $cheapestVendorProduct->price ?: 0);
                    $priceDisplay = number_format($price, 2);
                    $inStock = ($cheapestVendorProduct->stock ?? 0) > 0;
                }
                
                return [
                    'id' => $similarProduct->id,
                    'name' => $similarProduct->name,
                    'slug' => $similarProduct->slug,
                    'image' => isset($similarProduct->images[0]) ? $similarProduct->images[0] : null,
                    'vendor_product_id' => $cheapestVendorProduct->id,
                    'vendor_name' => $cheapestVendorProduct->vendor->store_name,
                    'vendor_slug' => $cheapestVendorProduct->vendor->slug,
                    'currency' => $vendorCurrency,
                    'price_display' => $priceDisplay,
                    'has_variations' => $hasVariations,
                    'variations_count' => $cheapestVendorProduct->variations->count(),
                    'in_stock' => $inStock,
                ];
            })
            ->filter()
            ->values();

        $mappedProduct['similar_products'] = $similarProducts;


        // Get reviews using VendorProductReview model
        $reviews = VendorProductReview::where('vendor_product_id', $vendor_product_id)
            ->with('user')
            ->latest()
            ->paginate(10);

        // Transform reviews to include user data
        $reviews->getCollection()->transform(function ($review) {
            return [
                'id' => $review->id,
                'user_id' => $review->user_id,
                'user_name' => $review->user?->name ?? 'Anonyme',
                'user_avatar' => $review->user?->avatar,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toDateTimeString(),
                'created_at_human' => $review->created_at?->diffForHumans(),
                'verified_purchase' => $this->isVerifiedPurchase($review->user_id, $review->vendor_product_id),
            ];
        });

        $mappedProduct['reviews'] = $reviews;
        $mappedProduct['rating'] = VendorProductReview::where('vendor_product_id', $vendor_product_id)
            ->avg('rating') ?? 0;
        $mappedProduct['reviews_count'] = VendorProductReview::where('vendor_product_id', $vendor_product_id)
            ->count();
        $allReviews = VendorProductReview::where('vendor_product_id', $vendor_product_id)->get();
        \Log::info('All reviews for this product:');
        foreach ($allReviews as $review) {
            \Log::info('Review ID: ' . $review->id . ', User ID: ' . $review->user_id . ', Comment: ' . $review->comment);
        }
                
        if ($request->user()) {
            \Log::info('Current user ID: ' . $request->user()->id);
        }
        // Check if user has reviewed
        if ($request->user()) {
            $userId = $request->user()->id;
            
            \Log::info('Checking review for user: ' . $userId);
            \Log::info('Vendor product ID: ' . $vendor_product_id);
            
            $userReview = VendorProductReview::where('vendor_product_id', $vendor_product_id)
                ->where('user_id', $userId)  // Make sure this matches!
                ->first();
            
            if ($userReview) {
                \Log::info('Found user review: ' . $userReview->id);
                $mappedProduct['user_review'] = [
                    'id' => $userReview->id,
                    'rating' => $userReview->rating,
                    'comment' => $userReview->comment,
                    'created_at' => $userReview->created_at?->toDateTimeString(),
                    'is_approved' => $userReview->is_approved ?? true,
                ];
            } else {
                \Log::info('No review found for this user');
                $mappedProduct['user_review'] = null;
            }
        } else {
            \Log::info('No user logged in');
            $mappedProduct['user_review'] = null;
        }

        return response()->json($mappedProduct);
    }

    /**
     * Check if user has purchased this product
     */
    private function isVerifiedPurchase($userId, $vendorProductId)
    {
        // Implement your logic to check if user purchased this product
        // For example:
        return \App\Models\OrderItem::where('user_id', $userId)
            ->where('vendor_product_id', $vendorProductId)
            ->exists();
    }

    private function mapProductDetail($product, $vendorProduct, $currencyCode)
    {
        // Get vendor's original currency
        $vendorCurrency = $vendorProduct->vendor->currency->code ?? 'USD';
        
        // Helper function to format numbers
        $formatNumber = function($value) {
            return $value ? (float) $value : 0;
        };

        // Get variations - WITH NULL CHECK
        $variations = collect([]);
        if ($vendorProduct->variations && $vendorProduct->variations->count() > 0) {
            $variations = $vendorProduct->variations->map(function ($variation) {
                return [
                    'id' => $variation->id,
                    'attributes' => $variation->attributes ?? [],
                    'pretty_attributes' => $this->formatAttributes($variation->attributes),
                    'sku' => $variation->sku,
                    'stock' => $variation->stock ?? 0,
                    'price' => (float) ($variation->price ?? 0),
                    'sale_price' => $variation->sale_price ? (float) $variation->sale_price : null,
                    'image' => $variation->image,
                ];
            })->values();
        }

        // Get wholesale tiers - WITH NULL CHECK
        $wholesaleTiers = collect([]);
        if ($vendorProduct->wholesaleTiers && $vendorProduct->wholesaleTiers->count() > 0) {
            $wholesaleTiers = $vendorProduct->wholesaleTiers->map(function ($tier) {
                return [
                    'id' => $tier->id,
                    'min_qty' => (int) $tier->min_qty,
                    'max_qty' => $tier->max_qty ? (int) $tier->max_qty : null,
                    'price' => (float) $tier->price,
                ];
            })->values();
        }

        // Get wholesale tiers by variation - WITH NULL CHECK
        $wholesaleTiersByVariation = [];
        if ($vendorProduct->variations && $vendorProduct->variations->count() > 0) {
            foreach ($vendorProduct->variations as $variation) {
                if ($variation->wholesaleTiers && $variation->wholesaleTiers->isNotEmpty()) {
                    $wholesaleTiersByVariation[$variation->id] = $variation->wholesaleTiers->map(function ($tier) {
                        return [
                            'id' => $tier->id,
                            'min_qty' => (int) $tier->min_qty,
                            'max_qty' => $tier->max_qty ? (int) $tier->max_qty : null,
                            'price' => (float) $tier->price,
                        ];
                    })->values()->toArray();
                }
            }
        }

        // Build variation attributes for UI - WITH NULL CHECK
        $variationAttributes = [];
        if ($vendorProduct->variations && $vendorProduct->variations->count() > 0) {
            foreach ($vendorProduct->variations as $variation) {
                $attributes = $variation->attributes ?? [];
                foreach ($attributes as $key => $value) {
                    if (!isset($variationAttributes[$key])) {
                        $variationAttributes[$key] = [];
                    }
                    if (!in_array($value, $variationAttributes[$key])) {
                        $variationAttributes[$key][] = $value;
                    }
                }
            }
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description ?? '',
            'short_description' => $product->short_description,
            'slug' => $product->slug,
            'images' => $product->images ?? [],
            'description_images' => $product->description_images ?? [],
            'video' => $product->video,
            'video_type' => $product->video_type,
            'video_thumbnail' => $product->video_thumbnail,
            'video_url' => $product->video_url,
            
            // Vendor info
            'vendor_product_id' => $vendorProduct->id,
            'vendor_id' => $vendorProduct->vendor_id,
            'vendor_name' => $vendorProduct->vendor->store_name ?? '',
            'vendor_slug' => $vendorProduct->vendor->slug ?? '',
            'vendor_logo' => $vendorProduct->vendor->logo_path,
            'vendor_rating' => $vendorProduct->vendor->rating ?? 0,
            
            // Currency - USE VENDOR'S ORIGINAL CURRENCY
            'currency' => $vendorCurrency,
            
            // Price info - NO CONVERSION
            'display_price' => $formatNumber($vendorProduct->sale_price ?? $vendorProduct->price),
            'original_price' => $formatNumber($vendorProduct->price),
            'sale_price' => $vendorProduct->sale_price ? $formatNumber($vendorProduct->sale_price) : null,
            'sale_end' => $vendorProduct->sale_end,
            'discount' => ($vendorProduct->sale_price && $vendorProduct->price > 0) ? 
                round(100 - (($vendorProduct->sale_price / $vendorProduct->price) * 100)) : null,
            
            // Stock
            'stock' => $vendorProduct->stock ?? 0,
            'sku' => $vendorProduct->sku,
            'min_order_quantity' => $vendorProduct->min_order_quantity ?? 1,
            'max_order_quantity' => $vendorProduct->max_order_quantity ?? 10,
            
            // Variations
            'has_variations' => $variations->isNotEmpty(),
            'variations' => $variations,
            'variation_attributes' => $variationAttributes,
            
            // Wholesale
            'has_wholesale' => $wholesaleTiers->isNotEmpty() || !empty($wholesaleTiersByVariation),
            'wholesale_tiers' => $wholesaleTiers,
            'wholesale_tiers_by_variation' => $wholesaleTiersByVariation,
            
            // Product info
            'specifications' => $product->specifications,
            'category' => $product->category,
            'brand' => $product->brand,
            'tags' => $product->tags,
            
            'has_vendor' => true,
        ];
    }

    private function formatAttributes($attributes)
    {
        if (empty($attributes)) return '';
        
        $parts = [];
        foreach ($attributes as $key => $value) {
            $parts[] = ucfirst($key) . ': ' . $value;
        }
        return implode(', ', $parts);
    }
}