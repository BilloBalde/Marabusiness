<?php

namespace App\Livewire;

use App\Models\Vendor;
use App\Models\VendorFollow;
use App\Models\VendorReview;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class VendorPage extends Component
{
    use WithPagination;

    public $vendor;
    public $sort = 'popular';
    public $category = '';
    public $min = 0;
    public $max = 1000000;
    
    // Follow state
    public $isFollowing = false;
    
    // Review state
    public $rating = 0;
    public $comment = '';
    public $userVendorReview = null;
    public $isEditingReview = false;
    
    // Modal state
    public $showDeleteModal = false;
    
    // Reviews
    public $reviews = [];
    public $reviewsLoaded = 10;
    
    // Validation rules
    protected $rules = [
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'required|string|min:10|max:500',
    ];

    public function mount($slug)
    {
        $this->vendor = Vendor::where('slug', $slug)->firstOrFail();
        
        if (Auth::check()) {
            $this->isFollowing = $this->vendor->is_followed_by_current_user;
            
            $this->userVendorReview = VendorReview::where('user_id', Auth::id())
                ->where('vendor_id', $this->vendor->id)
                ->first();
                
            if ($this->userVendorReview) {
                $this->rating = $this->userVendorReview->rating;
                $this->comment = $this->userVendorReview->comment;
            }
        }
        
        $this->loadReviews();
    }

    public function loadReviews()
    {
        $this->reviews = $this->vendor->getAllReviews()
            ->take($this->reviewsLoaded);
    }

    public function loadMoreReviews()
    {
        $this->reviewsLoaded += 10;
        $this->loadReviews();
    }

    public function toggleFollow()
    {
        if (!Auth::check()) {
            $this->dispatch('show-toast', 
                message: 'Please login to follow vendors',
                type: 'warning'
            );
            return;
        }
        
        if ($this->isFollowing) {
            VendorFollow::where('user_id', Auth::id())
                ->where('vendor_id', $this->vendor->id)
                ->delete();
                
            $this->isFollowing = false;
        } else {
            VendorFollow::create([
                'user_id' => Auth::id(),
                'vendor_id' => $this->vendor->id,
            ]);
            
            $this->isFollowing = true;
        }
        
        $this->vendor->refresh();
    }

    public function updateRating($newRating)
    {
        $this->rating = $newRating;
    }

    public function submitReview()
    {
        if (!Auth::check()) {
            $this->dispatch('show-toast', 
                message: 'Please login to submit a review',
                type: 'warning'
            );
            return;
        }
        
        // Validate - this will show errors if validation fails
        $this->validate();
        
        if ($this->userVendorReview) {
            $this->userVendorReview->update([
                'rating' => $this->rating,
                'comment' => $this->comment,
                'is_approved' => false,
            ]);
            
            $message = 'Review updated successfully! It will be visible after approval.';
        } else {
            $this->userVendorReview = VendorReview::create([
                'user_id' => Auth::id(),
                'vendor_id' => $this->vendor->id,
                'rating' => $this->rating,
                'comment' => $this->comment,
                'is_approved' => false,
            ]);
            
            $message = 'Review submitted successfully! It will be visible after approval.';
        }
        
        $this->isEditingReview = false;
        $this->showDeleteModal = false;
        
        $this->dispatch('show-toast', 
            message: $message,
            type: 'success'
        );
        
        $this->loadReviews();
    }

    public function editReview()
    {
        $this->isEditingReview = true;
    }

    public function cancelEditReview()
    {
        $this->isEditingReview = false;
        if ($this->userVendorReview) {
            $this->rating = $this->userVendorReview->rating;
            $this->comment = $this->userVendorReview->comment;
        } else {
            $this->rating = 0;
            $this->comment = '';
        }
    }

    public function deleteReview()
    {
        if ($this->userVendorReview) {
            $this->userVendorReview->delete();
            $this->userVendorReview = null;
            $this->rating = 0;
            $this->comment = '';
            $this->isEditingReview = false;
            $this->showDeleteModal = false;
            
            $this->dispatch('show-toast', 
                message: 'Review deleted successfully',
                type: 'success'
            );
            
            $this->loadReviews();
        }
    }

    public function updating($field)
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = $this->vendor->products()
            ->withPivot('id', 'price', 'sale_price', 'discount_percent', 'stock', 'is_active')
            ->wherePivot('is_active', true)
            ->when($this->category !== '', fn ($q) => 
                $q->where('category_id', $this->category)
            )
            ->when($this->min > 0, fn ($q) => 
                $q->where('vendor_product.price', '>=', $this->min)
            )
            ->when($this->max < 1000000, fn ($q) => 
                $q->where('vendor_product.price', '<=', $this->max)
            )
            ->when($this->sort === 'price_asc', fn ($q) => 
                $q->orderBy('vendor_product.price', 'asc')
            )
            ->when($this->sort === 'price_desc', fn ($q) => 
                $q->orderBy('vendor_product.price', 'desc')
            )
            ->when($this->sort === 'newest', fn ($q) => 
                $q->orderBy('vendor_product.created_at', 'desc')
            )
            ->paginate(20);

        return view('livewire.vendor-page', [
            'products' => $products,
            'vendor' => $this->vendor,
            'reviews' => $this->reviews,
            'totalReviews' => count($this->vendor->getAllReviews()),
        ]);
    }

    public function addToCart($vendorProductId)
    {
        $total_count = \App\Helpers\CartManagement::addItemToCart($vendorProductId);
        
        $this->dispatch('cart-updated');
        $this->dispatch('cart-added');
    }
}