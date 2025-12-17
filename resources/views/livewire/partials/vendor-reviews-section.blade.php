<!-- Right Column: Reviews and Info -->
<div class="lg:col-span-1">
    <!-- Review Form -->
    @if(auth()->check())
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
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
                        <button wire:click="deleteReview"
                                onclick="return confirm('Are you sure you want to delete your review?')"
                                class="text-sm text-red-600 hover:text-red-700">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </div>
                </div>
            @else
                <form wire:submit.prevent="submitReview">
                    <!-- Star Rating -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Rating
                        </label>
                        <div class="flex items-center space-x-1">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button"
                                        wire:click="$set('rating', {{ $i }})"
                                        class="{{ $i <= $rating ? 'text-yellow-400' : 'text-gray-300' }} focus:outline-none text-2xl">
                                    <i class="fas fa-star"></i>
                                </button>
                            @endfor
                        </div>
                        @error('rating') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    <!-- Comment -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Your Review
                        </label>
                        <textarea wire:model="comment"
                                  rows="4"
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-[#D4AF37] focus:border-[#D4AF37]"
                                  placeholder="Share your experience with this vendor..."></textarea>
                        @error('comment') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex gap-2">
                        <button type="submit"
                                class="flex-1 bg-[#D4AF37] text-white py-2 rounded-md hover:bg-[#c9a12f] transition-colors">
                            {{ $userVendorReview ? 'Update Review' : 'Submit Review' }}
                        </button>
                        
                        @if($isEditingReview && $userVendorReview)
                            <button type="button"
                                    wire:click="cancelEditReview"
                                    class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-md hover:bg-gray-300 transition-colors">
                                Cancel
                            </button>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    @endif
    
    <!-- Reviews List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
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