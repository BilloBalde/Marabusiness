<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VendorProductReview;
use App\Models\VendorProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    /**
     * Get reviews for a product with pagination
     */
    public function index($vendor_product_id)
    {
        $reviews = VendorProductReview::where('vendor_product_id', $vendor_product_id)
            ->with('user')
            ->latest()
            ->paginate(10);

        // Transform reviews
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

        return response()->json([
            'success' => true,
            'data' => $reviews,
            'message' => 'Reviews retrieved successfully'
        ]);
    }

    /**
     * Submit a new review
     */
    public function store(Request $request, $vendor_product_id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:3',
        ]);

        // Check if user already reviewed this product
        $existing = VendorProductReview::where('vendor_product_id', $vendor_product_id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product'
            ], 400);
        }

        $review = VendorProductReview::create([
            'vendor_product_id' => $vendor_product_id,
            'user_id' => Auth::id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => 'Review submitted successfully'
        ]);
    }

    /**
     * Update a review
     */
    public function update(Request $request, $review_id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:3',
        ]);

        $review = VendorProductReview::findOrFail($review_id);

        // Check if user owns this review
        if ($review->user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => 'Review updated successfully'
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy($review_id)
    {
        $review = VendorProductReview::findOrFail($review_id);

        // Check if user owns this review
        if ($review->user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }

    /**
     * Check if user has purchased this product
     */
    private function isVerifiedPurchase($userId, $vendorProductId)
    {
        return \App\Models\OrderItem::where('user_id', $userId)
            ->where('vendor_product_id', $vendorProductId)
            ->exists();
    }
}