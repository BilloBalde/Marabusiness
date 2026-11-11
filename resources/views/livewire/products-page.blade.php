<div class="bg-gray-50 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4">
        {{-- @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.products']) --}}

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            {{-- FILTERS --}}
            <div class="lg:col-span-1">
                {{-- On a phone this used to stack every category, every brand, the
                     price slider and the sort box in a row above the first product,
                     so a shopper scrolled the whole filter panel to reach anything
                     to buy. Collapsed by default below lg, always open at lg and up
                     (the x-data state is ignored there by lg:block), and the filters
                     themselves are still applied live either way. --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 lg:sticky lg:top-24"
                     x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between lg:pointer-events-none lg:cursor-default">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-sliders text-[#D4AF37]"></i>
                            Filtres
                        </h2>
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200 lg:hidden"
                           :class="{ 'rotate-180': open }"></i>
                    </button>

                    <div class="hidden lg:block mt-5" :class="{ '!block': open }">

                    {{-- CATEGORY --}}
                    <div class="mb-6 pb-6 border-b border-gray-100">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Catégories</h3>
                        <div class="space-y-2">
                            @foreach($categories as $cat)
                                <label class="flex items-center gap-2.5 text-sm text-gray-700 cursor-pointer hover:text-[#96731A] transition-colors">
                                    <input type="checkbox"
                                        wire:model.live="selectedCategories"
                                        value="{{ $cat->id }}"
                                        class="rounded border-gray-300 text-[#D4AF37] accent-[#D4AF37] focus:ring-[#D4AF37]/40">
                                    <span>{{ $cat->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- BRAND --}}
                    <div class="mb-6 pb-6 border-b border-gray-100">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Marques</h3>
                        <div class="space-y-2">
                            @foreach($brands as $brand)
                                <label class="flex items-center gap-2.5 text-sm text-gray-700 cursor-pointer hover:text-[#96731A] transition-colors">
                                    <input type="checkbox"
                                        wire:model.live="selectedBrands"
                                        value="{{ $brand->id }}"
                                        class="rounded border-gray-300 text-[#D4AF37] accent-[#D4AF37] focus:ring-[#D4AF37]/40">
                                    <span>{{ $brand->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- PRICE --}}
                    <div class="mb-6 pb-6 border-b border-gray-100">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Prix maximum</h3>
                        <input type="range"
                            min="0"
                            max="2000"
                            wire:model.live.debounce.300ms="price_range"
                            class="w-full accent-[#D4AF37]">
                        {{-- At 0 the filter is switched off (ProductsPage: if price_range
                             > 0), but the label read "0 USD", announcing a maximum price
                             of nothing over a list showing every product. --}}
                        <p class="text-sm font-medium mt-2 {{ $price_range > 0 ? 'text-[#96731A]' : 'text-gray-500' }}">
                            @if($price_range > 0)
                                Jusqu'à {{ number_format($price_range) }} USD
                            @else
                                Tous les prix
                            @endif
                        </p>
                    </div>

                    {{-- SORT --}}
                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Trier par</h3>
                        <select wire:model.live="sort" class="w-full border-gray-200 rounded-lg text-sm py-2.5 focus:border-[#D4AF37] focus:ring-[#D4AF37]/40">
                            <option value="latest">Nouveaux</option>
                            <option value="price">Prix</option>
                        </select>
                    </div>

                    </div>{{-- /collapsible filter body --}}
                </div>
            </div>

            {{-- RESULTS --}}
            <div class="lg:col-span-3">
                <h2 class="text-xl font-bold text-gray-800 mb-5">Tous les produits</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
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
                            <div class="group bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300"
                                 x-data="{ added: false }">

                                {{-- IMAGE --}}
                                <a href="/products/{{ $product->slug }}/{{ $vp_id }}" class="block overflow-hidden rounded-t-xl">
                                    <img src="{{ $img }}" class="w-full h-56 object-cover group-hover:scale-105 transition-transform duration-500">
                                </a>

                                <div class="p-4">
                                    {{-- NAME --}}
                                    <h3 class="text-sm font-semibold text-gray-800 line-clamp-2 mb-2">
                                        {{ $product->name }}
                                    </h3>

                                    {{-- VENDOR & STOCK --}}
                                    <a href="/vendor/{{ $vendor->slug }}"
                                       class="inline-flex items-center gap-1 mb-2 text-[11px] font-medium text-gray-500 hover:text-[#96731A] transition-colors">
                                        <i class="fa-solid fa-store text-[#D4AF37]"></i>
                                        {{ $shop }}
                                        @if($inStock)
                                            @if($hasVariations)
                                                <span class="text-gray-300">·</span> {{ $totalStock }} pcs au total
                                            @else
                                                <span class="text-gray-300">·</span> {{ $totalStock }} pcs
                                            @endif
                                        @else
                                            <span class="text-gray-300">·</span> <span class="text-red-500">Rupture</span>
                                        @endif
                                    </a>

                                    {{-- PRICE + Add to Wishlist --}}
                                    <div class="flex justify-between items-end mt-1">
                                        <div>
                                            @if($hasVariations)
                                                @if($priceRange)
                                                    <p class="text-[#96731A] font-bold text-base">
                                                        {{ number_format($minPrice, 2) }} - {{ number_format($maxPrice, 2) }} {{ $currency }}
                                                    </p>
                                                    <p class="text-xs text-gray-400">
                                                        {{ $vp->variations->count() }} variante(s)
                                                    </p>
                                                @else
                                                    <p class="text-[#96731A] font-bold text-base">
                                                        {{ number_format($minPrice, 2) }} {{ $currency }}
                                                    </p>
                                                    <p class="text-xs text-gray-400">
                                                        {{ $vp->variations->count() }} produit simple
                                                    </p>
                                                @endif
                                            @else
                                                @if($salePrice && $salePrice < $vp->price)
                                                    <p class="text-[#96731A] font-bold text-base">
                                                        {{ number_format($salePrice, 2) }} {{ $currency }}
                                                    </p>
                                                    <p class="line-through text-gray-400 text-xs">
                                                        {{ number_format($vp->price, 2) }} {{ $currency }}
                                                    </p>
                                                @else
                                                    <p class="text-[#96731A] font-bold text-base">
                                                        {{ number_format($displayPrice, 2) }} {{ $currency }}
                                                    </p>
                                                @endif
                                            @endif
                                        </div>

                                        @if($inStock)
                                            <button wire:click="addToWishlist({{ $vp_id }})"
                                                    @click="added = true"
                                                    class="p-2 rounded-full border border-[#D4AF37] bg-white hover:bg-[#FDF6E3] transition-colors">
                                                <i class="fa-solid fa-heart text-[#D4AF37]" x-show="!added"></i>
                                                <i class="fa-solid fa-check text-green-500" x-show="added"></i>
                                            </button>
                                        @else
                                            <button disabled class="p-2 rounded-full border bg-gray-100 cursor-not-allowed">
                                                <i class="fa-solid fa-ban text-gray-400"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                    @empty
                        <div class="col-span-full text-center py-16">
                            <div class="inline-flex w-16 h-16 items-center justify-center rounded-full bg-gray-100 mb-4">
                                <i class="fa-solid fa-box-open text-gray-400 text-2xl"></i>
                            </div>
                            <p class="text-gray-600 font-medium">Aucun produit trouvé.</p>
                        </div>
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
