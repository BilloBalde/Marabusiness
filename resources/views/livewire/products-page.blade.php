<div class="bg-gray-50 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4 flex flex-col lg:flex-row gap-6">
        {{-- @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.products']) --}}

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <h2 class="text-lg font-semibold mb-4">Filtres</h2>

                {{-- CATEGORY --}}
                <div class="mb-6">
                    <h3 class="font-medium text-gray-700 mb-2">Catégories</h3>
                    @foreach($categories as $cat)
                        <label class="flex items-center gap-2 text-sm mb-1">
                            <input type="checkbox"
                                wire:model.live="selectedCategories"
                                value="{{ $cat->id }}"
                                class="rounded border-gray-300 text-yellow-600">
                            <span>{{ $cat->name }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- BRAND --}}
                <div class="mb-6">
                    <h3 class="font-medium text-gray-700 mb-2">Marques</h3>
                    @foreach($brands as $brand)
                        <label class="flex items-center gap-2 text-sm mb-1">
                            <input type="checkbox"
                                wire:model.live="selectedBrands"
                                value="{{ $brand->id }}"
                                class="rounded border-gray-300 text-yellow-600">
                            <span>{{ $brand->name }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- PRICE --}}
                <div class="mb-6">
                    <h3 class="font-medium text-gray-700 mb-2">Prix Maximum</h3>
                    <input type="range"
                        min="0"
                        max="2000"
                        wire:model.live.debounce.300ms="price_range"
                        class="w-full accent-yellow-600">
                    {{-- At 0 the filter is switched off (ProductsPage: if price_range
                         > 0), but the label read "0 USD", announcing a maximum price
                         of nothing over a list showing every product. --}}
                    <p class="text-sm text-gray-600 mt-1">
                        @if($price_range > 0)
                            Jusqu'à {{ number_format($price_range) }} USD
                        @else
                            Tous les prix
                        @endif
                    </p>
                </div>

                {{-- SORT --}}
                <div class="mb-6">
                    <h3 class="font-medium text-gray-700 mb-2">Trier par</h3>
                    <select wire:model.live="sort" class="w-full border-gray-300 rounded-lg p-2">
                        <option value="latest">Nouveaux</option>
                        <option value="price">Prix</option>
                    </select>
                </div>
            </div>
            
            <div class="lg:col-span-2">
                <h2 class="text-xl font-bold mb-4">Tous les Produits</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($vendorProducts as $vp)
                        @php
                            $product = $vp->product;
                            $vendor = $vp->vendor;
                            $vp_id = $vp->id;
                            $currency = $vendor->currency?->code ?? 'USD';
                            $shop = $vendor->store_name;

                            // Check if product has variations
                            $hasVariations = $vp->has_variations && $vp->variations->count() > 0;
                            
                            // Calculate price display
                            if ($hasVariations) {
                                $minPrice = $vp->variations->min('price');
                                $maxPrice = $vp->variations->max('price');
                                $displayPrice = $minPrice;
                                $priceRange = $minPrice != $maxPrice;
                                $totalStock = $vp->variations->sum('stock');
                                $inStock = $totalStock > 0;
                                $salePrice = null; // No sale price for variations in this view
                            } else {
                                $displayPrice = $vp->sale_price ?? $vp->price ?? 0;
                                $priceRange = false;
                                $totalStock = $vp->stock ?? 0;
                                $inStock = $totalStock > 0;
                                $salePrice = $vp->sale_price ?? null;
                            }

                            // via.placeholder.com has been shut down, so this rendered a
                            // broken image on every product without a picture — and sent
                            // each visitor's browser to a dead third party to find that
                            // out. The placeholder ships with the app now.
                            $img = isset($product->images[0])
                                ? url('uploads/' . $product->images[0])
                                : url('uploads/default.png');
                        @endphp

                        @if($displayPrice > 0 || $hasVariations)
                            <div class="border border-gray-200 rounded-lg bg-white shadow-sm hover:shadow-lg transition"
                                 x-data="{ added: false }">

                                {{-- IMAGE --}}
                                <a href="/products/{{ $product->slug }}/{{ $vp_id }}">
                                    <img src="{{ $img }}" class="w-full h-56 object-cover rounded-t-lg">
                                </a>
                                
                                <div class="p-3">
                                    {{-- NAME --}}
                                    <h3 class="text-md font-semibold line-clamp-2 mb-1">
                                        {{ $product->name }}
                                    </h3>

                                    {{-- VENDOR & STOCK --}}
                                    <p class="text-xs text-gray-500 mb-1">
                                        <a href="/vendor/{{ $vendor->slug }}"
                                           class="inline-flex items-center px-4 py-2 text-xs font-semibold border rounded-full hover:bg-gray-900 hover:text-white">
                                            🏬 {{ $shop }}
                                            @if($inStock)
                                                @if($hasVariations)
                                                    – {{ $totalStock }} pcs total
                                                @else
                                                    – {{ $totalStock }} pcs
                                                @endif
                                            @else
                                                – Rupture
                                            @endif
                                        </a>
                                    </p>

                                    {{-- PRICE + Add to Wishlist --}}
                                    <div class="flex justify-between items-center mt-2">
                                        <div>
                                            @if($hasVariations)
                                                @if($priceRange)
                                                    <p class="text-yellow-600 font-bold text-lg">
                                                        {{ number_format($minPrice, 2) }} - {{ number_format($maxPrice, 2) }} {{ $currency }}
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        {{ $vp->variations->count() }} variante(s)
                                                    </p>
                                                @else
                                                    <p class="text-yellow-600 font-bold text-lg">
                                                        {{ number_format($minPrice, 2) }} {{ $currency }}
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        {{ $vp->variations->count() }} produit simple
                                                    </p>
                                                @endif
                                            @else
                                                @if($salePrice && $salePrice < $vp->price)
                                                    <p class="text-yellow-600 font-bold text-lg">
                                                        {{ number_format($salePrice, 2) }} {{ $currency }}
                                                    </p>
                                                    <p class="line-through text-gray-400 text-xs">
                                                        {{ number_format($vp->price, 2) }} {{ $currency }}
                                                    </p>
                                                @else
                                                    <p class="text-yellow-600 font-bold text-lg">
                                                        {{ number_format($displayPrice, 2) }} {{ $currency }}
                                                    </p>
                                                @endif
                                            @endif
                                        </div>

                                        @if($inStock)
                                            <button wire:click="addToWishlist({{ $vp_id }})"
                                                    @click="added = true"
                                                    class="p-2 rounded-full border border-[#D4AF37] bg-white hover:bg-[#FFF7DF] transition">
                                                <i class="fa-solid fa-heart text-[#D4AF37]" x-show="!added"></i>
                                                <i class="fa-solid fa-check text-green-500" x-show="added"></i>
                                            </button>
                                        @else
                                            <button disabled class="p-2 rounded-full border bg-gray-300 cursor-not-allowed">
                                                <i class="fa-solid fa-ban text-gray-500"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                    @empty
                        <p class="text-gray-600 col-span-3 text-center py-10">
                            Aucun produit trouvé.
                        </p>
                    @endforelse
                </div>

                {{-- PAGINATION --}}
                <div class="mt-8">
                    {{ $vendorProducts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>