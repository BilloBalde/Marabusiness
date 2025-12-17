<div class="bg-gray-50 min-h-screen py-10">

    <div class="max-w-7xl mx-auto px-6">
        @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.vendor'])

        <!-- Vendor Header -->
        <div class="bg-white rounded-xl shadow-sm mb-6 p-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    @if($vendor->logo_path)
                        <img src="{{ asset($vendor->logo_path) }}"
                             class="w-20 h-20 rounded-full border shadow object-cover">
                    @else
                        <div class="w-20 h-20 rounded-full bg-gray-200 border shadow flex items-center justify-center">
                            <span class="text-2xl font-bold text-gray-600">
                                {{ substr($vendor->store_name, 0, 1) }}
                            </span>
                        </div>
                    @endif

                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                            {{ $vendor->store_name }}
                        </h1>

                        <p class="text-gray-600 mt-1">
                            {{ $vendor->description ?? 'Boutique Officielle' }}
                        </p>

                        <!-- Stats -->
                        <div class="flex items-center gap-4 text-sm text-gray-600 mt-2">
                            <!-- Rating -->
                            <div class="flex items-center gap-1">
                                @php
                                    $rating = $vendor->vendor_rating;
                                    $fullStars = floor($rating);
                                    $hasHalfStar = $rating - $fullStars >= 0.5;
                                @endphp
                                
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $fullStars)
                                        <i class="fas fa-star text-yellow-400 text-xs"></i>
                                    @elseif($hasHalfStar && $i == $fullStars + 1)
                                        <i class="fas fa-star-half-alt text-yellow-400 text-xs"></i>
                                    @else
                                        <i class="far fa-star text-gray-300 text-xs"></i>
                                    @endif
                                @endfor
                                <span class="ml-1">{{ number_format($rating, 1) }}</span>
                            </div>
                            
                            <!-- Followers -->
                            <div class="flex items-center gap-1">
                                <i class="fas fa-users text-gray-400 text-xs"></i>
                                <span>{{ $vendor->followers_count }} followers</span>
                            </div>
                            
                            <!-- Reviews Count -->
                            @if($vendor->vendor_reviews_count > 0)
                                <span class="text-gray-500">
                                    {{ $vendor->vendor_reviews_count }} review(s)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Follow Button -->
                <button wire:click="toggleFollow"
                        class="px-6 py-2 rounded-full font-medium transition-all
                               {{ $isFollowing 
                                  ? 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300' 
                                  : 'bg-[#D4AF37] text-white hover:bg-[#c9a12f]' }}">
                    <i class="fas fa-{{ $isFollowing ? 'check' : 'plus' }} mr-2"></i>
                    {{ $isFollowing ? 'Following' : 'Follow' }}
                </button>
            </div>
        </div>

        <!-- Delete Review Modal -->
        @if($userVendorReview && $showDeleteModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" style="display: block;">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <!-- Background overlay -->
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity modal-backdrop"
                         wire:click="$set('showDeleteModal', false)"></div>

                    <!-- Modal panel -->
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full modal-content">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                                        Delete Review
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Are you sure you want to delete your review? This action cannot be undone.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" 
                                    wire:click="deleteReview"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Delete
                            </button>
                            <button type="button" 
                                    wire:click="$set('showDeleteModal', false)"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#D4AF37] sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Products -->
            <div class="lg:col-span-2">
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-4">
                            <select wire:model="sort" class="border rounded-lg px-3 py-2">
                                <option value="popular">Popular</option>
                                <option value="newest">Newest</option>
                                <option value="price_asc">Price: Low to High</option>
                                <option value="price_desc">Price: High to Low</option>
                            </select>
                            
                            <select wire:model="category" class="border rounded-lg px-3 py-2">
                                <option value="">All Categories</option>
                                <!-- Add your categories here -->
                            </select>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <input type="number" wire:model="min" placeholder="Min" 
                                   class="w-24 border rounded-lg px-3 py-2">
                            <span>-</span>
                            <input type="number" wire:model="max" placeholder="Max" 
                                   class="w-24 border rounded-lg px-3 py-2">
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                @if($products->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($products as $product)
                        <div class="bg-white rounded-xl shadow hover:shadow-lg transition p-4">
                            
                            <!-- Product Image -->
                            @php
                                $firstImage = null;
                                
                                // Check if images exists and get first image
                                if ($product->images) {
                                    if (is_array($product->images)) {
                                        $firstImage = $product->images[0] ?? null;
                                    } elseif (is_string($product->images)) {
                                        $imagesArray = json_decode($product->images, true);
                                        $firstImage = $imagesArray[0] ?? null;
                                    }
                                }
                            @endphp

                            @if($firstImage)
                                <img src="{{ url('uploads/' . $firstImage) }}" 
                                     class="w-full h-48 object-cover rounded-lg mb-3">
                            @else
                                <div class="w-full h-48 bg-gray-200 rounded-lg flex items-center justify-center mb-3">
                                    <i class="fas fa-box-open text-gray-400 text-3xl"></i>
                                </div>
                            @endif
                            
                            <!-- Product Info -->
                            <h3 class="font-semibold text-gray-800 line-clamp-2">
                                {{ $product->name }}
                            </h3>
                            
                            <!-- Price -->
                            <div class="mt-2">
                                <span class="text-lg font-bold text-gray-900">
                                    {{ number_format($product->pivot->sale_price ?? $product->pivot->price, 2) }} {{ $vendor->currency_code }}
                                </span>
                                @if($product->pivot->sale_price && $product->pivot->price > $product->pivot->sale_price)
                                    <span class="text-sm text-gray-500 line-through ml-2">
                                        {{ number_format($product->pivot->price, 2) }}  {{ $vendor->currency_code }}
                                    </span>
                                    <span class="text-sm text-green-600 ml-2">
                                        -{{ round((($product->pivot->price - $product->pivot->sale_price) / $product->pivot->price) * 100) }}%
                                    </span>
                                @endif
                            </div>
                            
                            <!-- Stock Status -->
                            @if($product->pivot->stock > 0)
                                <p class="text-sm text-green-600 mt-1">
                                    <i class="fas fa-check-circle mr-1"></i> In Stock
                                </p>
                            @else
                                <p class="text-sm text-red-600 mt-1">
                                    <i class="fas fa-times-circle mr-1"></i> Out of Stock
                                </p>
                            @endif
                            
                            <!-- Add to Cart Button -->
                            @if($product->pivot->stock > 0)
                                <button wire:click="addToCart({{ $product->pivot->id }})"
                                        class="w-full mt-3 bg-[#D4AF37] text-white py-2 rounded-lg hover:bg-[#c9a12f] transition-colors">
                                    <i class="fas fa-cart-plus mr-2"></i> Add to Cart
                                </button>
                            @else
                                <div class="w-full mt-3 bg-gray-100 text-gray-500 py-2 rounded-lg text-center">
                                    Out of Stock
                                </div>
                            @endif
                            
                            <!-- View Details Link -->
                            <a href="{{ url('/products/' . $product->slug . '/' . $product->pivot->id) }}"
                               class="block text-center text-sm text-[#D4AF37] hover:text-[#c9a12f] mt-2">
                                View Details
                            </a>
                        </div>
                        @endforeach
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                        <i class="fas fa-box-open text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-600">No products available from this vendor yet.</p>
                    </div>
                @endif
            </div>
            
            <!-- Right Column: Reviews -->
            <div class="lg:col-span-1">
                <!-- Review Form -->
                @if(auth()->check())
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">
                            {{ $userVendorReview ? 'Your Vendor Review' : 'Review This Vendor' }}
                        </h3>
                        
                        @if(!$isEditingReview && $userVendorReview)
                            <div class="mb-4">
                                <div class="flex items-center mb-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $userVendorReview->rating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                    @endfor
                                    <span class="ml-2 text-sm text-gray-500">
                                        {{ $userVendorReview->is_approved ? 'Approved' : 'Pending Approval' }}
                                    </span>
                                </div>
                                <p class="text-gray-700 mb-4">{{ $userVendorReview->comment }}</p>
                                <div class="flex gap-2">
                                    <button wire:click="editReview"
                                            class="text-sm text-[#D4AF37] hover:text-[#c9a12f]">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </button>
                                    <button wire:click="$set('showDeleteModal', true)"
                                            class="text-sm text-red-600 hover:text-red-700">
                                        <i class="fas fa-trash mr-1"></i> Delete
                                    </button>
                                </div>
                            </div>
                        @else
                            <form wire:submit.prevent="submitReview">
                                <!-- Star Rating - Simple and clean -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Rating
                                    </label>
                                    <div class="flex items-center space-x-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <button type="button"
                                                    wire:click="updateRating({{ $i }})"
                                                    class="focus:outline-none text-2xl">
                                                <i class="fas fa-star {{ $i <= $rating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                            </button>
                                        @endfor
                                        <span class="ml-2 text-sm text-gray-600">
                                            @if($rating == 0)
                                                Select rating
                                            @elseif($rating == 1)
                                                1 star
                                            @else
                                                {{ $rating }} stars
                                            @endif
                                        </span>
                                    </div>
                                    @error('rating') 
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                                
                                <!-- Comment -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Your Review
                                    </label>
                                    <textarea wire:model="comment"
                                            rows="4"
                                            class="w-full border {{ $errors->has('comment') ? 'border-red-300' : 'border-gray-300' }} rounded-lg px-3 py-2 focus:ring-[#D4AF37] focus:border-[#D4AF37]"
                                            placeholder="Share your experience with this vendor..."></textarea>
                                    @error('comment') 
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @else
                                        <div class="text-xs {{ strlen($comment) < 10 ? 'text-red-500' : 'text-gray-500' }} mt-1">
                                            {{ strlen($comment) }}/500 characters {{ strlen($comment) < 10 ? '(Minimum 10 characters)' : '' }}
                                        </div>
                                    @enderror
                                </div>
                                
                                <!-- Submit Button - ALWAYS enabled, validation happens on submit -->
                                <div class="flex gap-2">
                                    <button type="submit"
                                            class="flex-1 bg-[#D4AF37] hover:bg-[#c9a12f] text-white py-2 rounded-lg transition-colors">
                                        {{ $userVendorReview ? 'Update Review' : 'Submit Review' }}
                                    </button>
                                    
                                    @if($isEditingReview && $userVendorReview)
                                        <button type="button"
                                                wire:click="cancelEditReview"
                                                class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                            Cancel
                                        </button>
                                    @endif
                                </div>
                            </form>
                        @endif
                    </div>
                @endif
                
                <!-- Reviews List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        Reviews ({{ $totalReviews }})
                    </h3>
                    
                    @if(count($reviews) > 0)
                        <div class="space-y-6 max-h-[500px] overflow-y-auto pr-2">
                            @foreach($reviews as $review)
                                <div class="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            @if($review->user->avatar)
                                                <img src="{{ asset($review->user->avatar) }}" 
                                                    alt="{{ $review->user->name }}"
                                                    class="w-8 h-8 rounded-full">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-gray-600">
                                                        {{ substr($review->user->name, 0, 1) }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div>
                                                <span class="font-medium text-gray-900">{{ $review->user->name }}</span>
                                                @if($review->type === 'product')
                                                    <span class="text-xs text-gray-500 block">
                                                        Review for: {{ $review->product_name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($review->created_at)->diffForHumans() }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center mb-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }} text-sm"></i>
                                        @endfor
                                    </div>
                                    
                                    <p class="text-gray-700 text-sm">{{ $review->comment }}</p>
                                </div>
                            @endforeach
                            
                            @if(count($reviews) < $totalReviews)
                                <button wire:click="loadMoreReviews"
                                        class="w-full text-center text-[#D4AF37] hover:text-[#c9a12f] py-2">
                                    <i class="fas fa-chevron-down mr-2"></i> Load More Reviews
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-comment-alt text-gray-400 text-3xl mb-3"></i>
                            <p class="text-gray-600">No reviews yet. Be the first to review this vendor!</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* Modal animations */
    .modal-backdrop {
        animation: fadeIn 0.3s ease-out;
    }
    
    .modal-content {
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Star hover effect */
    button:hover .fas.fa-star {
        transform: scale(1.1);
        transition: transform 0.2s;
    }
</style>