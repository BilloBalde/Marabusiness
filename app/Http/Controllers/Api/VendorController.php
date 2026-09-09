<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Support\VendorPresenter;
use App\Models\VendorFollow;
use App\Models\VendorReview;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Mail\VendorApplicationSubmitted;

class VendorController extends Controller
{
    /**
     * Get all active vendors with pagination
     */
    public function index(Request $request)
    {
        try {
            $query = VendorPresenter::eagerLoad(Vendor::query())
                ->where('is_active', true);

            // Search by store name
            if ($request->has('search') && !empty($request->search)) {
                $query->where('store_name', 'like', '%' . $request->search . '%');
            }

            // Filter by category
            if ($request->has('category_id') && !empty($request->category_id)) {
                $query->whereHas('products', function($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            switch ($sortBy) {
                case 'name':
                    $query->orderBy('store_name', $sortOrder);
                    break;
                case 'rating':
                    $query->orderBy('vendor_rating', $sortOrder);
                    break;
                case 'popular':
                    $query->orderBy('followers_count', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 16);
            $vendors = $query->paginate($perPage);

            // Transform vendors
            $vendors->getCollection()->transform(function ($vendor) use ($request) {
                return $this->formatVendor($vendor, $request);
            });

            return response()->json([
                'success' => true,
                'data' => $vendors,
                'message' => 'Vendors retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vendors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single vendor details by slug
     */
    public function show($slug, Request $request)
    {
        try {
            $vendor = Vendor::where('slug', $slug)
                ->where('is_active', true)
                ->with(['currency'])
                ->withCount(['followers', 'approvedVendorReviews as reviews_count'])
                ->firstOrFail();

            // Get products with filtering
            $products = $this->getVendorProducts($vendor, $request);

            // Get reviews with pagination
            $reviews = $this->getVendorReviews($vendor, $request);

            // Check if current user follows this vendor
            $isFollowing = false;
            $userReview = null;
            
            if (Auth::check()) {
                $userId = Auth::id();
                \Log::info('Current user ID: ' . $userId);
                
                $isFollowing = VendorFollow::where('user_id', $userId)
                    ->where('vendor_id', $vendor->id)
                    ->exists();

                $userReview = VendorReview::where('user_id', $userId)
                    ->where('vendor_id', $vendor->id)
                    ->first();
                
                if ($userReview) {
                    \Log::info('User review found: ' . $userReview->id);
                    \Log::info('User review data: ' . json_encode($this->formatReview($userReview)));
                } else {
                    \Log::info('No user review found for user ' . $userId);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'vendor' => $this->formatVendor($vendor, $request, true),
                    'products' => $products,
                    'reviews' => $reviews,
                    'is_following' => $isFollowing,
                    'user_review' => $userReview ? $this->formatReview($userReview) : null,
                ],
                'message' => 'Vendor retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get vendor products with filtering
     */
    public function products($vendorId, Request $request)
    {
        try {
            $vendor = Vendor::findOrFail($vendorId);

            $products = $this->getVendorProducts($vendor, $request);

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Vendor products retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendor reviews
     */
    public function reviews($vendorId, Request $request)
    {
        try {
            $vendor = Vendor::findOrFail($vendorId);

            $reviews = $this->getVendorReviews($vendor, $request);

            return response()->json([
                'success' => true,
                'data' => $reviews,
                'message' => 'Vendor reviews retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reviews',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleFollow($vendorId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $vendor = Vendor::findOrFail($vendorId);
            
            // Check if already following
            $existingFollow = VendorFollow::where('user_id', $user->id)
                ->where('vendor_id', $vendorId)
                ->first();

            if ($existingFollow) {
                // Unfollow
                $existingFollow->delete();
                $isFollowing = false;
                $message = 'Vendor unfollowed';
            } else {
                // Follow
                VendorFollow::create([
                    'user_id' => $user->id,
                    'vendor_id' => $vendorId
                ]);
                $isFollowing = true;
                $message = 'Vendor followed';
            }

            $followersCount = VendorFollow::where('vendor_id', $vendorId)->count();

            \Log::info('Toggle follow result:', [
                'user_id' => $user->id,
                'vendor_id' => $vendorId,
                'is_following' => $isFollowing,
                'followers_count' => $followersCount
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'is_following' => $isFollowing,  // Make sure this is included
                'followers_count' => $followersCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Toggle follow error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkFollow($vendorId)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'is_following' => false]);
            }

            $isFollowing = VendorFollow::where('user_id', $user->id)
                ->where('vendor_id', $vendorId)
                ->exists();

            return response()->json([
                'success' => true,
                'is_following' => $isFollowing
            ]);
        } catch (\Exception $e) {
            \Log::error('Check follow error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server Error'
            ], 500);
        }
    }

    /**
     * Submit or update vendor review
     */
    public function submitReview(Request $request, $vendorId)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to submit a review'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|min:10|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $vendor = Vendor::findOrFail($vendorId);
            $userId = Auth::id();

            $review = VendorReview::updateOrCreate(
                [
                    'user_id' => $userId,
                    'vendor_id' => $vendorId,
                ],
                [
                    'rating' => $request->rating,
                    'comment' => $request->comment,
                    'is_approved' => false, // Requires admin approval
                ]
            );

            $message = $review->wasRecentlyCreated 
                ? 'Review submitted successfully! It will be visible after approval.'
                : 'Review updated successfully! It will be visible after approval.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $this->formatReview($review)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete vendor review
     */
    public function deleteReview($vendorId)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login to delete review'
                ], 401);
            }

            $review = VendorReview::where('user_id', Auth::id())
                ->where('vendor_id', $vendorId)
                ->firstOrFail();

            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendor statistics
     */
    public function stats($vendorId)
    {
        try {
            $vendor = Vendor::findOrFail($vendorId);

            $stats = [
                'total_products' => $vendor->vendorProducts()->count(),
                'total_orders' => $vendor->orders()->count(),
                'total_reviews' => $vendor->approvedVendorReviews()->count(),
                'average_rating' => $vendor->vendor_rating,
                'total_followers' => $vendor->followers()->count(),
                'total_sales' => $vendor->orders()->sum('grand_total'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Vendor stats retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vendor stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Add this method to VendorController.php
public function userVendorStatus()
{
    try {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'has_vendor' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $hasVendor = Vendor::where('user_id', $user->id)->exists();

        return response()->json([
            'success' => true,
            'has_vendor' => $hasVendor,
            'message' => 'Vendor status retrieved successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve vendor status',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Get featured vendors
     */
    public function featured()
    {
        try {
            $vendors = Vendor::where('is_active', true)
                ->where('is_featured', true)
                ->withCount(['followers', 'approvedVendorReviews'])
                ->orderBy('vendor_rating', 'desc')
                ->limit(8)
                ->get()
                ->map(fn($vendor) => $this->formatVendor($vendor));

            return response()->json([
                'success' => true,
                'data' => $vendors,
                'message' => 'Featured vendors retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured vendors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendor products with filtering
     */
    private function getVendorProducts($vendor, Request $request)
    {
        $query = $vendor->products()
            ->withPivot('id', 'price', 'sale_price', 'discount_percent', 'stock', 'is_active', 'created_at')
            ->wherePivot('is_active', true)
            ->with(['category', 'brand']);

        // Filter by category
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->min_price > 0) {
            $query->where('vendor_product.price', '>=', $request->min_price);
        }

        if ($request->has('max_price') && $request->max_price < 1000000) {
            $query->where('vendor_product.price', '<=', $request->max_price);
        }

        // Search by product name
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'popular');
        $sortOrder = $request->get('sort_order', 'desc');

        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('vendor_product.price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('vendor_product.price', 'desc');
                break;
            case 'newest':
                $query->orderBy('vendor_product.created_at', 'desc');
                break;
            case 'popular':
            default:
                $query->orderBy('vendor_product.created_at', 'desc');
                break;
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage);

        // (debug block removed: it re-ran the full query on every request)
        // Transform products
        $products->getCollection()->transform(function ($product) use ($vendor) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'images' => $product->images ?? [],
                'category' => $product->category?->name,
                'brand' => $product->brand?->name,
                'vendor_product_id' => $product->pivot->id,
                'price' => (float) $product->pivot->price,
                'sale_price' => $product->pivot->sale_price ? (float) $product->pivot->sale_price : null,
                'discount_percent' => $product->pivot->discount_percent,
                'stock' => $product->pivot->stock ?? 0,
                'has_variations' => $product->pivot->has_variations ?? false,
                'currency' => $vendor->currency?->code ?? 'USD',
                'created_at' => $product->pivot->created_at?->toDateTimeString(),
            ];
        });

        return $products;
    }

    public function apply(Request $request)
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'store_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
                'currency_id' => 'required|exists:currencies,id',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'zip_code' => 'nullable|string|max:50',
                'logo' => 'nullable|image|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Handle user creation or get existing user
            $user = Auth::user();
            
            if (!$user) {
                // Validate user fields for guests
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users',
                    'password' => 'required|string|min:6|confirmed',
                ]);
                
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);
                
                Auth::login($user);
            }

            // Check if user already has vendor
            if (Vendor::where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a vendor profile.'
                ], 400);
            }

            // Assign vendor role
            if (!$user->hasRole('vendor')) {
                $user->assignRole('vendor');
            }

            // Handle logo upload
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('vendors', 'public_uploads');
            }

            // Create vendor
            $vendor = Vendor::create([
                'user_id' => $user->id,
                'currency_id' => $request->currency_id,
                'store_name' => $request->store_name,
                'slug' => Str::slug($request->store_name) . '-' . Str::lower(Str::random(6)),
                'description' => $request->description,
                'is_active' => false,
                'approved_at' => null,
                'logo_path' => $logoPath,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country ?? 'Guinée',
                'zip_code' => $request->zip_code,
            ]);

            // Send email notification (you can implement this)
            try {
                Mail::to($user->email)->send(new VendorApplicationSubmitted($vendor, $user));
            } catch (\Exception $e) {
                \Log::error('Failed to send vendor application email: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully! Your vendor account is pending approval.',
                'data' => $vendor
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendor reviews with pagination
     */
    private function getVendorReviews($vendor, Request $request)
    {
        $perPage = $request->get('reviews_per_page', 10);
        
        $reviews = VendorReview::where('vendor_id', $vendor->id)
            ->where('is_approved', true)
            ->with(['user' => function($q) {
                $q->select(['id', 'name', 'avatar']);
            }])
            ->latest()
            ->paginate($perPage);

        $reviews->getCollection()->transform(fn($review) => $this->formatReview($review));

        return $reviews;
    }

    /**
     * Format vendor for API response
     */
    private function formatVendor($vendor, $request = null, $detailed = false)
    {
        $formatted = VendorPresenter::present($vendor);

        if ($detailed) {
            $formatted['address'] = $vendor->address;
            $formatted['phone'] = $vendor->phone;
            $formatted['email'] = $vendor->email;
            $formatted['website'] = $vendor->website;
            $formatted['social_links'] = $vendor->social_links;
            $formatted['rating_breakdown'] = [
                '5' => VendorReview::where('vendor_id', $vendor->id)->where('rating', 5)->count(),
                '4' => VendorReview::where('vendor_id', $vendor->id)->where('rating', 4)->count(),
                '3' => VendorReview::where('vendor_id', $vendor->id)->where('rating', 3)->count(),
                '2' => VendorReview::where('vendor_id', $vendor->id)->where('rating', 2)->count(),
                '1' => VendorReview::where('vendor_id', $vendor->id)->where('rating', 1)->count(),
            ];
        }

        return $formatted;
    }

    /**
     * Format review for API response
     */
    private function formatReview($review)
    {
        return [
            'id' => $review->id,
            'user_id' => $review->user_id,
            'user_name' => $review->user?->name ?? 'Anonymous',
            'user_avatar' => $review->user?->avatar ? url($review->user->avatar) : null,
            'rating' => (int) $review->rating,
            'comment' => $review->comment,
            'is_approved' => (bool) $review->is_approved,
            'created_at' => $review->created_at?->toDateTimeString(),
            'created_at_human' => $review->created_at?->diffForHumans(),
        ];
    }
}