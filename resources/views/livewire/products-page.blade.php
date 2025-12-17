<div class="bg-gray-50 min-h-screen py-6">

    <div class="max-w-7xl mx-auto px-4 flex gap-6">

        {{-- ======================= --}}
        {{-- SIDEBAR FILTERS --}}
        {{-- ======================= --}}
        <aside class="w-64 bg-white shadow rounded-lg p-4 h-full sticky top-4">

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
                <p class="text-sm text-gray-600 mt-1">{{ $price_range }} USD</p>
            </div>

            {{-- SORT --}}
            <div class="mb-6">
                <h3 class="font-medium text-gray-700 mb-2">Trier par</h3>
                <select wire:model.live="sort" class="w-full border-gray-300 rounded-lg p-2">
                    <option value="latest">Nouveaux</option>
                    <option value="price">Prix</option>
                </select>
            </div>

        </aside>


        {{-- ======================= --}}
        {{-- PRODUCT LIST --}}
        {{-- ======================= --}}
        <main class="flex-1">

            <h2 class="text-xl font-bold mb-4">Tous les Produits</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">

                @forelse($vendorProducts as $vp)

                    @php
                        $product  = $vp->product;
                        $vendor   = $vp->vendor;
                        $vp_id    = $vp->id;
                        $price     = $vp->price;
                        $salePrice = $vp->sale_price;
                        $stock     = $vp->stock;
                        $currency  = $vendor->currency?->code ?? 'USD';

                        $shop      = $vendor->store_name;

                        $img = isset($product->images[0])
                            ? url('uploads/' . $product->images[0])
                            : 'https://via.placeholder.com/400x400?text=No+Image';
                    @endphp

                    @if($price > 0)
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

                                {{-- VENDOR --}}
                                <p class="text-xs text-gray-500 mb-1">
                                    <a href="/vendor/{{ $vendor->slug }}"
                                        class="inline-flex items-center px-4 py-2 text-xs font-semibold border rounded-full hover:bg-gray-900 hover:text-white">
                                        🏬 {{ $shop }} – Stock: {{ $stock }} Pcs
                                    </a>
                                </p>

                                {{-- PRICE + Add to Cart --}}
                                <div class="flex justify-between items-center mt-2">

                                    <div>
                                        @if($salePrice)
                                            <p class="text-yellow-600 font-bold text-lg">
                                                {{ number_format($salePrice, 2) }} {{ $currency }}
                                            </p>
                                            <p class="line-through text-gray-400 text-xs">
                                                {{ number_format($price, 2) }} {{ $currency }}
                                            </p>
                                        @else
                                            <p class="text-yellow-600 font-bold text-lg">
                                                {{ number_format($price, 2) }} {{ $currency }}
                                            </p>
                                        @endif
                                    </div>

                                    @if ($stock > 0)
                                        <button
                                            wire:click="addToCart({{ $vp_id }})"
                                            @click="added = true"
                                            class="p-2 rounded-full border border-[#D4AF37] bg-white">
                                            <i class="fa-solid fa-cart-shopping text-[#D4AF37]" x-show="!added"></i>
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

        </main>

    </div>

</div>
