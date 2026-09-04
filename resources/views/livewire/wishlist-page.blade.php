<div class="bg-gray-50 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4">
        @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.wishlist', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
        <h1 class="text-2xl font-bold mb-4">Wishlist</h1>

        @if(empty($wishlist_items))
            <div class="text-center py-10 bg-white rounded-xl shadow">
                <p class="text-gray-500 text-lg mb-4">Your wishlist is empty</p>
                <a href="/products"
                   class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-[#D4AF37] rounded-lg hover:bg-[#C9A227]">
                    Browse products
                    <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="md:col-span-3 space-y-4">
                    @foreach ($wishlist_items as $item)
                        @php
                            $isSelected = in_array($item['wishlist_key'], $selected_items, true);
                            $variationText = $this->getVariationText($item);
                        @endphp
                        <div class="bg-white rounded-xl shadow p-4">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        class="mt-2"
                                        wire:click="toggleItem('{{ $item['wishlist_key'] }}')"
                                        {{ $isSelected ? 'checked' : '' }}
                                    >
                                    <img src="{{ url('uploads/' . ($item['image'] ?? 'no.jpg')) }}"
                                         class="w-20 h-20 rounded-lg object-cover border flex-shrink-0">
                                </div>

                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-sm line-clamp-2">
                                        {{ $item['product_name'] ?? 'Produit sans nom' }}
                                    </h3>

                                    @if(!empty($variationText))
                                        <div class="mt-1 text-xs text-gray-600 bg-blue-50 p-2 rounded">
                                            <span class="font-medium">Variation:</span>
                                            {{ $variationText }}
                                        </div>
                                    @endif

                                    <p class="text-[#D4AF37] font-bold text-lg mt-1">
                                        {{ Number::currency($item['base_price'] ?? 0, $item['currency'] ?? 'USD') }}
                                    </p>
                                </div>

                                <div class="flex items-center justify-end">
                                    <button
                                        wire:click="removeItem('{{ $item['wishlist_key'] }}')"
                                        class="text-gray-400 hover:text-red-500 transition">
                                        <i class="fa-solid fa-trash text-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="md:col-span-1">
                    <div class="bg-white rounded-xl shadow p-5 sticky top-24">
                        <h2 class="text-lg font-semibold mb-3">Actions</h2>

                        <div class="space-y-2">
                            <button
                                wire:click="addSelectedToCart"
                                @if (count($selected_items) === 0) disabled @endif
                                class="w-full py-2 text-sm font-semibold rounded-lg border border-[#D4AF37] text-[#D4AF37] hover:bg-[#FFF7DF] disabled:opacity-50 disabled:cursor-not-allowed">
                                Add selected to cart
                            </button>
                            <button
                                wire:click="clearWishlist"
                                class="w-full py-2 text-sm font-semibold rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50">
                                Clear wishlist
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
