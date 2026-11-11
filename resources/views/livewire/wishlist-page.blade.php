<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.wishlist', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Ma liste de souhaits</h1>
            <div class="w-16 h-1 bg-[#D4AF37] mt-4 rounded-full"></div>
        </div>

        @if(empty($wishlist_items))
            <div class="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="inline-flex w-16 h-16 items-center justify-center rounded-full bg-gray-100 mb-4">
                    <i class="fa-solid fa-heart text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-600 text-lg font-medium mb-5">Votre liste de souhaits est vide</p>
                <a href="/products"
                   class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-[#D4AF37] rounded-lg shadow-sm hover:bg-[#C9A227] hover:shadow-md transition-all">
                    Découvrir les produits
                    <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-start">
                <div class="md:col-span-3 space-y-4">
                    @foreach ($wishlist_items as $item)
                        @php
                            $isSelected = in_array($item['wishlist_key'], $selected_items, true);
                            $variationText = $this->getVariationText($item);
                        @endphp
                        <div wire:key="{{ $item['wishlist_key'] }}"
                             class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-4 sm:p-5">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        class="mt-2 w-4 h-4 rounded border-gray-300 text-[#D4AF37] focus:ring-[#D4AF37]"
                                        wire:click="toggleItem('{{ $item['wishlist_key'] }}')"
                                        {{ $isSelected ? 'checked' : '' }}
                                    >
                                    <img src="{{ url('uploads/' . ($item['image'] ?? 'no.jpg')) }}"
                                         class="w-20 h-20 rounded-xl object-cover border border-gray-100 flex-shrink-0">
                                </div>

                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-800 line-clamp-2">
                                        {{ $item['product_name'] ?? 'Produit sans nom' }}
                                    </h3>

                                    @if(!empty($variationText))
                                        <div class="mt-1.5 inline-block text-xs font-medium text-[#8a6d1a] bg-[#D4AF37]/10 px-2.5 py-1 rounded-full">
                                            {{ $variationText }}
                                        </div>
                                    @endif

                                    <p class="text-[#D4AF37] font-bold text-lg mt-2">
                                        {{ Number::currency($item['base_price'] ?? 0, $item['currency'] ?? 'USD') }}
                                    </p>
                                </div>

                                <div class="flex items-center justify-end">
                                    <button
                                        wire:click="removeItem('{{ $item['wishlist_key'] }}')"
                                        class="w-9 h-9 flex items-center justify-center rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="md:col-span-1">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sticky top-24">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Actions</h2>

                        <div class="space-y-2.5">
                            <button
                                wire:click="addSelectedToCart"
                                @if (count($selected_items) === 0) disabled @endif
                                class="w-full py-2.5 text-sm font-semibold rounded-lg bg-[#D4AF37] text-white hover:bg-[#C9A227] disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-[#D4AF37] transition-colors">
                                Ajouter la sélection au panier
                            </button>
                            <button
                                wire:click="clearWishlist"
                                class="w-full py-2.5 text-sm font-semibold rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                                Vider la liste
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
