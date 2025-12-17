<div class="bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="max-w-6xl mx-auto bg-white shadow rounded-lg p-6">
        @if(session()->has('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <!-- IMAGES -->
            <div>
                <img src="{{ url('uploads', $product->images[0] ?? 'no.jpg') }}"
                     class="w-full h-96 object-cover rounded-lg shadow">
                <div class="flex gap-2 mt-3">
                    @foreach ($product->images as $img)
                        <img src="{{ url('uploads', $img) }}"
                             class="w-20 h-20 object-cover rounded border hover:ring-[#D4AF37] hover:ring cursor-pointer">
                    @endforeach
                </div>
            </div>

            <!-- PRODUCT DETAILS -->
            <div class="space-y-6">
                <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
                <!-- VENDOR INFO -->
                <p class="text-xs text-gray-500 mb-1">
                    <a href="/vendor/{{ $vendor->slug }}"
                        class="inline-flex items-center px-4 py-2 text-xs font-semibold border rounded-full hover:bg-gray-900 hover:text-white">
                        🏬 {{ $vendor->store_name }}
                    </a>
                </p>
                <!-- STARS -->
                <div class="flex items-center gap-2">
                    <div class="flex text-yellow-500">
                        @for($i=1; $i<=5; $i++)
                            <i class="fa-solid fa-star {{ $avgRating >= $i ? '' : 'opacity-30' }}"></i>
                        @endfor
                    </div>
                    <span class="text-gray-500 text-sm">({{ $totalReviews }} avis)</span>
                </div>

                <!-- PRICE (UNIT) -->
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">
                        Prix unitaire actuel (selon quantité)
                    </p>
                    <p class="text-3xl font-bold text-[#D4AF37]">
                        {{ Number::currency($price, $vendor->currency->code ?? 'USD') }}
                    </p>

                    @if ($quantity > 1)
                        <p class="text-sm text-gray-600 mt-1">
                            Sous-total ({{ $quantity }} pièces) :
                            <span class="font-semibold">
                                {{ Number::currency($price * $quantity, $vendor->currency->code ?? 'USD') }}
                            </span>
                        </p>
                    @endif
                </div>

                <!-- WHOLESALE TIERS -->
                @if (!empty($wholesaleTiers))
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg border text-sm">
                        <p class="font-semibold mb-2">📦 Tarifs de gros</p>
                        <div class="space-y-1">
                            @foreach ($wholesaleTiers as $tier)
                                @php
                                    $min = $tier['min_qty'];
                                    $max = $tier['max_qty'];
                                    $label = $max
                                        ? "{$min} - {$max} pcs"
                                        : "{$min}+ pcs";
                                @endphp
                                <div class="flex justify-between">
                                    <span class="text-gray-700">{{ $label }}</span>
                                    <span class="font-semibold text-[#D4AF37]">
                                        {{ Number::currency($tier['price'], $vendor->currency->code ?? 'USD') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle"></i>
                            Le prix en gros s'applique sur la quantité totale de ce produit (toutes variations confondues)
                        </p>
                    </div>
                @endif

                <!-- VARIATIONS SELECTION -->
                @if(!empty($variationOptions))
                    <div class="space-y-4">
                        <p class="font-semibold text-gray-700">📝 Sélectionnez vos variations</p>
                        @foreach($variationOptions as $attribute => $value)
                            <div>
                                <p class="font-medium capitalize mb-2">{{ $attribute }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <button 
                                        wire:click="selectVariation('{{ $attribute }}', '{{ $value }}')"
                                        type="button"
                                        class="px-4 py-2 rounded-lg border text-sm transition-all
                                            {{ ($selectedVariations[$attribute] ?? '') === $value
                                                ? 'bg-[#D4AF37] text-white border-[#D4AF37]' 
                                                : 'bg-white text-gray-700 border-gray-300 hover:border-[#D4AF37]' }}">
                                        {{ $value }}
                                    </button>
                                </div>
                            </div>
                        @endforeach

                        <!-- Selected Variations Display -->
                        @if($selectedVariationsText)
                            <div class="p-3 bg-blue-50 rounded-lg">
                                <p class="font-medium text-blue-700">Variations sélectionnées :</p>
                                <p class="text-sm text-blue-600">{{ $selectedVariationsText }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- CUSTOM NOTE -->
                <div>
                    <label for="customNote" class="block font-medium text-gray-700 mb-2">
                        📝 Note supplémentaire pour le vendeur (optionnel)
                    </label>
                    <textarea 
                        wire:model="customNote"
                        id="customNote"
                        rows="2"
                        class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent"
                        placeholder="Ex: Livraison spéciale, emballage cadeau, etc."
                    ></textarea>
                </div>

                <!-- STOCK -->
                <p class="text-sm {{ $stock > 0 ? 'text-green-600' : 'text-red-600' }}">
                    @if($stock > 0)
                        <i class="fas fa-check-circle"></i> {{ $stock }} en stock
                    @else
                        <i class="fas fa-times-circle"></i> Rupture de stock
                    @endif
                </p>

                <!-- QUANTITY CONTROLS -->
                <div class="w-32 mb-8">
                    <label class="w-full pb-1 text-xl font-semibold text-gray-700 border-b border-blue-300">
                        Quantité
                    </label>
                    <div class="relative flex flex-row w-full h-10 mt-6 bg-transparent rounded-lg">
                        <button wire:click='decreaseQty' 
                                class="w-20 h-full text-gray-600 bg-gray-300 rounded-l outline-none cursor-pointer hover:bg-gray-400">
                            <span class="m-auto text-2xl font-thin">-</span>
                        </button>
                        <input wire:model='quantity' 
                               type="number" 
                               readonly 
                               class="flex items-center w-full font-semibold text-center text-gray-700 placeholder-gray-700 bg-gray-300 outline-none focus:outline-none text-md">
                        <button wire:click='increaseQty' 
                                class="w-20 h-full text-gray-600 bg-gray-300 rounded-r outline-none cursor-pointer hover:bg-gray-400">
                            <span class="m-auto text-2xl font-thin">+</span>
                        </button>
                    </div>
                </div>

                <!-- ADD TO CART BUTTON -->
                @if($stock > 0)
                    <div class="flex space-x-4 mt-6">
                        <button wire:click="addToCart"
                                wire:loading.attr="disabled"
                                class="flex-1 w-full bg-[#D4AF37] text-white py-3 rounded-lg font-semibold shadow hover:bg-[#c9a12f] transition flex items-center justify-center">
                            <span wire:loading.remove>
                                <i class="fas fa-shopping-cart mr-2"></i> Ajouter au panier
                            </span>
                            <span wire:loading>
                                <i class="fas fa-spinner fa-spin mr-2"></i> Ajout en cours...
                            </span>
                        </button>
                        <!-- RFQ Button -->
                        {{-- <div class="flex-1">
                            <livewire:rfq-button 
                                :productId="$product->id"
                                :vendorProductId="$vendorProduct->id"
                                :vendorId="$vendorProduct->vendor_id"
                            />
                        </div> --}}
                    </div>
                @else
                    <button disabled class="w-full bg-gray-400 text-white py-3 rounded-lg opacity-70 cursor-not-allowed">
                        <i class="fas fa-times-circle mr-2"></i> Rupture de stock
                    </button>
                @endif
            </div>
        </div>

        <!-- FULL DESCRIPTION -->
        <div class="mt-10 p-6 bg-white rounded-lg border">
            <h2 class="text-xl font-semibold mb-3">Description du Produit</h2>
            {!! $product->description !!}
        </div>

        <!-- REVIEWS SECTION -->
        <div class="mt-12">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Avis des clients</h2>

            @if(auth()->check())
                @if(!$hasReviewed && !$editingReview)
                    <div class="bg-gray-100 p-4 rounded-lg mb-6">
                        <h3 class="font-semibold mb-2">Laisser un avis</h3>

                        <div class="flex gap-1 text-yellow-500 text-xl mb-3">
                            @for ($i = 1; $i <= 5; $i++)
                                <button wire:click="$set('rating', {{ $i }})">
                                    <i class="fa-solid fa-star {{ $rating >= $i ? '' : 'opacity-30' }}"></i>
                                </button>
                            @endfor
                        </div>

                        <textarea wire:model="comment"
                                  class="w-full p-3 border rounded-lg mb-3"
                                  placeholder="Votre avis..."></textarea>

                        <button wire:click="submitReview"
                                class="bg-[#D4AF37] text-white px-5 py-2 rounded-lg">
                            Envoyer l'avis
                        </button>

                        @if($verifiedPurchase)
                            <p class="text-green-600 text-sm mt-2">✔ Achat vérifié</p>
                        @endif
                    </div>
                @endif

                @if($editingReview)
                    <div class="bg-yellow-50 p-4 rounded-lg mb-6">
                        <h3 class="font-semibold mb-2">Modifier votre avis</h3>

                        <div class="flex gap-1 text-yellow-500 text-xl mb-3">
                            @for ($i = 1; $i <= 5; $i++)
                                <button wire:click="$set('rating', {{ $i }})">
                                    <i class="fa-solid fa-star {{ $rating >= $i ? '' : 'opacity-30' }}"></i>
                                </button>
                            @endfor
                        </div>

                        <textarea wire:model="comment"
                                  class="w-full p-3 border rounded-lg mb-3"></textarea>

                        <button wire:click="updateReview"
                                class="bg-green-600 text-white px-5 py-2 rounded-lg">
                            Mettre à jour
                        </button>
                    </div>
                @endif

                @if($hasReviewed && !$editingReview)
                    <div class="p-4 bg-green-50 rounded-lg mb-6 flex justify-between">
                        <p class="font-semibold">Vous avez déjà laissé un avis.</p>
                        <button wire:click="editReview"
                                class="text-blue-600 hover:underline text-sm">
                            Modifier
                        </button>
                    </div>
                @endif
            @else
                <div class="p-4 bg-blue-50 rounded-lg mb-6 text-blue-700">
                    Connectez-vous pour laisser un avis.
                </div>
            @endif

            <!-- REVIEWS LIST -->
            @foreach ($reviews as $rev)
                <div class="border-b py-4">
                    <div class="flex justify-between items-center">
                        <p class="font-semibold text-gray-800">{{ $rev->user->name }}</p>
                        <div class="flex text-yellow-500">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="fa-solid fa-star {{ $rev->rating >= $i ? '' : 'opacity-30' }}"></i>
                            @endfor
                        </div>
                    </div>
                    <p class="text-gray-600 mt-1">{{ $rev->comment }}</p>
                    @if(\App\Models\OrderItem::where('product_id', $product->id)
                        ->whereHas('order', fn($q) => $q->where('user_id', $rev->user_id))
                        ->exists())
                        <p class="text-sm text-green-600 mt-1">✔ Achat vérifié</p>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- RELATED PRODUCTS -->
        <div class="mt-12">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Produits similaires</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach ($similarProducts as $sim)
                    @php
                        $relatedVendorProduct = $sim->vendorProducts()
                            ->with('vendor.currency')
                            ->orderBy('price')
                            ->first();
                    @endphp
                    
                    @if($relatedVendorProduct)
                        <a href="{{ route('product-show', [$sim->slug, $relatedVendorProduct->id]) }}"
                           class="bg-white rounded-lg p-3 shadow hover:shadow-lg transition block">
                            <img src="{{ url('uploads', $sim->images[0] ?? 'no.jpg') }}"
                                 class="w-full h-40 object-cover rounded">
                            <h3 class="mt-2 text-sm line-clamp-2 font-medium">{{ $sim->name }}</h3>
                            <p class="text-[#D4AF37] font-bold mt-1">
                                {{ Number::currency(
                                    $relatedVendorProduct->sale_price ?: $relatedVendorProduct->price,
                                    $relatedVendorProduct->vendor->currency->code ?? 'USD'
                                ) }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $relatedVendorProduct->vendor->store_name }}
                            </p>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>