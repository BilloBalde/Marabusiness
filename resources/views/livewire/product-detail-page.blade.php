<div class="bg-gradient-to-b from-gray-50 to-white min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb (add this) -->
        <nav class="flex mb-6 text-sm" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="/" class="text-gray-500 hover:text-[#D4AF37]">Accueil</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-xs text-gray-400 mx-2"></i>
                        <a href="/products" class="text-gray-500 hover:text-[#D4AF37]">Produits</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-xs text-gray-400 mx-2"></i>
                        <span class="text-gray-700 font-medium">{{ Str::limit($product->name, 30) }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Success Message -->
        @if(session()->has('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center shadow-sm">
                <i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i>
                {{ session('success') }}
            </div>
        @endif

        <!-- Main Product Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 lg:gap-8 p-6 lg:p-8">
                
                <!-- LEFT COLUMN: Product Images -->
                <div class="space-y-4">
                    <!-- Main Image/Video Display -->
                    <div class="relative w-full aspect-square rounded-xl overflow-hidden bg-gray-100 border border-gray-200 group">
                        @if($mainMediaType === 'video')
                            @if($product->video_type === 'youtube')
                                <iframe class="w-full h-full" src="https://www.youtube.com/embed/{{ $product->getYoutubeId() }}?autoplay=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                            @elseif($product->video_type === 'vimeo')
                                <iframe class="w-full h-full" src="https://player.vimeo.com/video/{{ $product->getVimeoId() }}?autoplay=1" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
                            @elseif($product->video)
                                <video class="w-full h-full object-contain" controls autoplay poster="{{ $product->video_thumbnail ? url('uploads/' . $product->video_thumbnail) : '' }}">
                                    <source src="{{ url('uploads/' . $product->video) }}" type="video/mp4">
                                </video>
                            @endif
                        @else
                            <img id="mainImage" 
                                src="{{ url('uploads', $currentImage) }}"
                                class="w-full h-full object-contain cursor-zoom-in transition-transform duration-300 group-hover:scale-105"
                                @click="openLightbox = true">
                            
                            <!-- Zoom hint -->
                            <div class="absolute bottom-3 right-3 bg-white/80 backdrop-blur-sm rounded-full px-3 py-1 text-xs text-gray-600 shadow-sm">
                                <i class="fas fa-search-plus mr-1"></i> Cliquez pour zoomer
                            </div>
                            
                            <!-- Play Button for Video Thumbnails - Now at Top Right -->
                            @if($product->video)
                                <button wire:click="showVideo" 
                                        class="absolute top-3 right-3 w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-110 transition-all duration-200 z-10 group/play">
                                    <i class="fas fa-play text-[#D4AF37] text-sm ml-0.5"></i>
                                    <span class="absolute -top-8 right-0 bg-gray-900 text-white text-xs py-1 px-2 rounded opacity-0 group-hover/play:opacity-100 transition whitespace-nowrap">
                                        Voir la vidéo
                                    </span>
                                </button>
                            @endif
                        @endif
                    </div>

                    <!-- Thumbnails Gallery -->
                    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300">
                        <!-- Image Thumbnails -->
                        @foreach ($product->images as $index => $img)
                            <button wire:click="selectImage({{ $index }})" class="flex-shrink-0 w-20 h-20 rounded-lg border-2 overflow-hidden transition-all duration-200 hover:border-[#D4AF37] {{ $currentImage === $img && $mainMediaType === 'image' ? 'border-[#D4AF37] ring-2 ring-[#D4AF37]/20' : 'border-gray-200' }}">
                                <img src="{{ url('uploads', $img) }}" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                        
                        <!-- Video Thumbnail -->
                        @if($product->video)
                            <button wire:click="showVideo" class="relative flex-shrink-0 w-20 h-20 rounded-lg border-2 overflow-hidden transition-all duration-200 hover:border-[#D4AF37] {{ $mainMediaType === 'video' ? 'border-[#D4AF37] ring-2 ring-[#D4AF37]/20' : 'border-gray-200' }}">
                                @if($product->video_thumbnail)
                                    <img src="{{ url('uploads', $product->video_thumbnail) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center">
                                        <i class="fas fa-play text-white text-xl"></i>
                                    </div>
                                @endif
                                <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                    <i class="fas fa-play text-white text-sm"></i>
                                </div>
                                <span class="absolute bottom-1 right-1 text-xs bg-black/70 text-white px-1 rounded">
                                    <i class="fas fa-video"></i>
                                </span>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- RIGHT COLUMN: Product Details -->
                <div class="space-y-6 lg:space-y-8">
                    <!-- Vendor & Ratings Row -->
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <a href="/vendor/{{ $vendor->slug }}" class="group inline-flex items-center px-4 py-2 bg-gray-50 hover:bg-gray-100 rounded-full transition border border-gray-200">
                            <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">🏬 {{ $vendor->store_name }}</span>
                            <i class="fas fa-chevron-right text-xs text-gray-400 ml-2 group-hover:text-gray-600"></i>
                        </a>
                        
                        <div class="flex items-center gap-3">
                            <div class="flex items-center">
                                @for($i=1; $i<=5; $i++)
                                    <i class="fa-solid fa-star text-sm {{ $avgRating >= $i ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                @endfor
                            </div>
                            <span class="text-sm text-gray-600">
                                {{ number_format($avgRating, 1) }} ({{ $totalReviews }} avis)
                            </span>
                        </div>
                    </div>

                    <!-- Product Title -->
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 leading-tight">{{ $product->name }}</h1>
                        @if($product->short_description)
                            <p class="text-gray-600 mt-2">{{ $product->short_description }}</p>
                        @endif
                    </div>

                    <!-- Price Display -->
                    <div class="bg-gradient-to-r from-yellow-50 to-amber-50 rounded-xl p-6 border border-yellow-100">
                        <div class="flex items-baseline justify-between flex-wrap gap-4">
                            <div>
                                <p class="text-sm text-gray-600 uppercase tracking-wider mb-1">Prix unitaire</p>
                                @php
                                    // Safe price calculation
                                    $displayPrice = 0;
                                    if ($price !== null && $price > 0) {
                                        $displayPrice = (float) $price;
                                    } elseif ($basePrice !== null && $basePrice > 0) {
                                        $displayPrice = (float) $basePrice;
                                    } elseif ($vendorProduct && $vendorProduct->sale_price) {
                                        $displayPrice = (float) $vendorProduct->sale_price;
                                    } elseif ($vendorProduct && $vendorProduct->price) {
                                        $displayPrice = (float) $vendorProduct->price;
                                    } elseif ($selectedVariation && $selectedVariation->sale_price) {
                                        $displayPrice = (float) $selectedVariation->sale_price;
                                    } elseif ($selectedVariation && $selectedVariation->price) {
                                        $displayPrice = (float) $selectedVariation->price;
                                    }
                                    
                                    // Safe currency code
                                    $currencyCode = 'USD';
                                    if ($vendor && $vendor->currency && $vendor->currency->code) {
                                        $currencyCode = $vendor->currency->code;
                                    } elseif ($vendorProduct && $vendorProduct->vendor && $vendorProduct->vendor->currency && $vendorProduct->vendor->currency->code) {
                                        $currencyCode = $vendorProduct->vendor->currency->code;
                                    }
                                @endphp
                                <span class="text-4xl font-bold text-[#D4AF37]">{{ number_format($displayPrice, 2) }}</span>
                                <span class="text-xl text-gray-600 ml-2">{{ $currencyCode }}</span>
                            </div>
                            
                            @if ($quantity > 1)
                                <div class="bg-white px-4 py-2 rounded-lg shadow-sm">
                                    <p class="text-sm text-gray-600">Total ({{ $quantity }} pièces)</p>
                                    <p class="text-xl font-bold text-[#D4AF37]">{{ number_format($displayPrice * $quantity, 2) }} {{ $currencyCode }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Variation Selection -->
                    @if(!empty($variationAttributes))
                        <div class="space-y-4 bg-gray-50 p-5 rounded-xl">
                            <p class="font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-tags text-[#D4AF37] mr-2"></i>
                                Sélectionnez vos variations
                            </p>
                            
                            @foreach($variationAttributes as $attribute => $values)
                                <div>
                                    <p class="text-sm font-medium text-gray-700 mb-2 capitalize">{{ $attribute }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($values as $value)
                                            <button wire:click="selectAttribute('{{ $attribute }}', '{{ $value }}')" type="button"
                                                class="px-4 py-2 rounded-lg border-2 text-sm font-medium transition-all duration-200
                                                    {{ ($selectedAttributes[$attribute] ?? '') === $value
                                                        ? 'bg-[#D4AF37] text-white border-[#D4AF37] shadow-md transform scale-105' 
                                                        : 'bg-white text-gray-700 border-gray-200 hover:border-[#D4AF37] hover:bg-yellow-50' }}">
                                                {{ $value }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <!-- Selected Variation Display -->
                            @if($selectedVariationsText)
                                <div class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-100">
                                    <p class="text-sm text-blue-800 flex items-center">
                                        <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                                        <span class="font-medium">Variation sélectionnée :</span>
                                        <span class="ml-2">{{ $selectedVariationsText }}</span>
                                    </p>
                                    @if($selectedVariation)
                                        <p class="text-sm text-blue-600 mt-1 flex items-center">
                                            <i class="fas fa-boxes mr-2"></i>
                                            Stock disponible: <span class="font-bold ml-1">{{ $stock }} pièces</span>
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Custom Note -->
                    <div>
                        <label for="customNote" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-pen text-gray-400 mr-2"></i>
                            Note pour le vendeur (optionnel)
                        </label>
                        <textarea wire:model="customNote" id="customNote" rows="2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent transition"
                            placeholder="Instructions spéciales, emballage cadeau, message..."></textarea>
                    </div>

                    <!-- Quantity & Actions -->
                    <div class="space-y-4">
                        <!-- Quantity Selector -->
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-medium text-gray-700 w-20">Quantité:</span>
                            <div class="flex items-center border-2 border-gray-200 rounded-lg overflow-hidden">
                                <button wire:click='decreaseQty' wire:loading.attr="disabled" @if($stock <= 0) disabled @endif
                                    class="w-10 h-10 flex items-center justify-center bg-gray-50 hover:bg-gray-100 transition disabled:opacity-50">
                                    <i class="fas fa-minus text-gray-600 text-sm"></i>
                                </button>
                                <input wire:model='quantity' type="number" readonly
                                    class="w-16 h-10 text-center font-semibold border-x-2 border-gray-200 focus:outline-none">
                                <button wire:click='increaseQty' wire:loading.attr="disabled" @if($stock <= 0) disabled @endif
                                    class="w-10 h-10 flex items-center justify-center bg-gray-50 hover:bg-gray-100 transition disabled:opacity-50">
                                    <i class="fas fa-plus text-gray-600 text-sm"></i>
                                </button>
                            </div>
                            
                            <!-- Stock Status -->
                            <div class="flex items-center">
                                @if($stock > 0)
                                    <span class="text-sm text-green-600 flex items-center">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        {{ $stock }} en stock
                                    </span>
                                @else
                                    <span class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-times-circle mr-1"></i>
                                        Rupture de stock
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @if($stock > 0)
                                <button wire:click="addToCart" wire:loading.attr="disabled"
                                    class="group relative bg-[#D4AF37] hover:bg-[#c9a12f] text-white py-4 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5 overflow-hidden">
                                    <span wire:loading.remove class="flex items-center justify-center">
                                        <i class="fas fa-shopping-cart mr-2"></i>
                                        Ajouter au panier
                                    </span>
                                    <span wire:loading class="flex items-center justify-center">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>
                                        Ajout en cours...
                                    </span>
                                    <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                                </button>
                            @else
                                <button disabled class="bg-gray-200 text-gray-500 py-4 rounded-xl font-semibold cursor-not-allowed flex items-center justify-center">
                                    <i class="fas fa-times-circle mr-2"></i>
                                    Rupture de stock
                                </button>
                            @endif

                            <button wire:click="addToWishlist"
                                class="group border-2 border-[#D4AF37] text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white py-4 rounded-xl font-semibold transition-all duration-200 transform hover:-translate-y-0.5 flex items-center justify-center">
                                <span wire:loading.remove>
                                    <i class="fas fa-heart mr-2 group-hover:animate-pulse"></i>
                                    Ajouter à la wishlist
                                </span>
                                <span wire:loading>
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Ajout en cours...
                                </span>
                            </button>
                        </div>

                        <!-- Payment/Shipping Icons -->
                        <div class="flex items-center justify-center gap-6 pt-4 text-gray-400 border-t border-gray-100">
                            <div class="flex items-center gap-2 text-sm" title="Paiement sécurisé">
                                <i class="fas fa-lock text-gray-500"></i>
                                <span>Paiement sécurisé</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm" title="Livraison rapide">
                                <i class="fas fa-truck text-gray-500"></i>
                                <span>Livraison rapide</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wholesale Tiers Section (if exists) -->
        @if($vendorProduct->has_variations && !empty($wholesaleTiersByVariation) || (!$vendorProduct->has_variations && !empty($wholesaleTiers)))
        <div class="mt-8 bg-white rounded-2xl shadow-xl p-6 lg:p-8">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-tags text-[#D4AF37] mr-3"></i>
                Tarifs de gros
            </h2>

            @if($vendorProduct->has_variations)
                <div class="space-y-4">
                    @foreach($availableVariations as $variation)
                        @php
                            $tiers = $wholesaleTiersByVariation[$variation->id] ?? [];
                            $variationPrice = $variation->sale_price ?? $variation->price ?? 0;
                        @endphp
                        
                        @if(!empty($tiers))
                            <div class="border border-gray-200 rounded-xl overflow-hidden">
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-gray-800">{{ $variation->pretty_attributes }}</span>
                                        <span class="text-sm text-gray-600">Base: {{ number_format($variationPrice, 2) }} {{ $currencyCode }}</span>
                                    </div>
                                </div>
                                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($tiers as $tier)
                                        <div class="bg-gradient-to-r from-yellow-50 to-amber-50 rounded-lg p-3 border border-yellow-100">
                                            <div class="text-sm text-gray-600">
                                                {{ $tier['min_qty'] }}{{ $tier['max_qty'] ? ' - '.$tier['max_qty'] : '+' }} pièces
                                            </div>
                                            <div class="text-lg font-bold text-[#D4AF37]">
                                                {{ number_format($tier['price'], 2) }} {{ $currencyCode }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @elseif(!empty($wholesaleTiers))
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($wholesaleTiers as $tier)
                        <div class="bg-gradient-to-r from-yellow-50 to-amber-50 rounded-xl p-4 border border-yellow-100">
                            <div class="text-sm text-gray-600 mb-1">
                                {{ $tier['min_qty'] }}{{ $tier['max_qty'] ? ' - '.$tier['max_qty'] : '+' }} pièces
                            </div>
                            <div class="text-2xl font-bold text-[#D4AF37]">
                                {{ number_format($tier['price'], 2) }} {{ $currencyCode }}
                            </div>
                            <div class="text-xs text-gray-500 mt-2">par unité</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

        <!-- Description & Details -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Description -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl p-6 lg:p-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-align-left text-[#D4AF37] mr-3"></i>
                    Description du produit
                </h2>
                <div class="prose prose-lg max-w-none text-gray-700">
                    {!! $product->description !!}
                </div>
            </div>

            <!-- Product Specifications -->
            <div class="bg-white rounded-2xl shadow-xl p-6 lg:p-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-clipboard-list text-[#D4AF37] mr-3"></i>
                    Caractéristiques
                </h2>
                
                <div class="space-y-4">
                    @if($product->brand)
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">Marque</span>
                        <span class="font-medium text-gray-800">{{ $product->brand->name }}</span>
                    </div>
                    @endif
                    
                    @if($product->category)
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">Catégorie</span>
                        <span class="font-medium text-gray-800">{{ $product->category->name }}</span>
                    </div>
                    @endif
                    
                    @if($product->weight)
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">Poids</span>
                        <span class="font-medium text-gray-800">{{ $product->weight }} {{ $product->weight_unit }}</span>
                    </div>
                    @endif
                    
                    @if($product->dimensions_formatted !== 'N/A')
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">Dimensions</span>
                        <span class="font-medium text-gray-800">{{ $product->dimensions_formatted }}</span>
                    </div>
                    @endif
                    
                    @if($product->cbm)
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-600">Volume (CBM)</span>
                        <span class="font-medium text-gray-800">{{ $product->cbm }} m³</span>
                    </div>
                    @endif
                    
                    <div class="flex justify-between py-2">
                        <span class="text-gray-600">Disponibilité</span>
                        @if($stock > 0)
                            <span class="text-green-600 font-medium">En stock ({{ $stock }} pièces)</span>
                        @else
                            <span class="text-red-600 font-medium">Rupture de stock</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Description Images -->
        @if(!empty($product->description_images) && is_array($product->description_images))
        <div class="mt-8 bg-white rounded-2xl shadow-xl p-6 lg:p-8">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-images text-[#D4AF37] mr-3"></i>
                Galerie d'images
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach ($product->description_images as $descImg)
                    <div class="group relative rounded-xl overflow-hidden border border-gray-200 hover:shadow-lg transition-all cursor-pointer">
                        <img src="{{ url('uploads', $descImg) }}" 
                            class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-110"
                            onclick="openLightboxImage('{{ url('uploads', $descImg) }}')">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                            <i class="fas fa-search-plus text-white text-2xl"></i>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- RELATED PRODUCTS -->
        @if(count($similarProducts) > 0)
        <div class="mt-8 bg-white rounded-2xl shadow-xl p-6 lg:p-8">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-th-large text-[#D4AF37] mr-3"></i>
                Produits similaires
            </h2>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 lg:gap-6">
                @foreach ($similarProducts as $sim)
                    @php
                        $relatedVendorProduct = $sim->vendorProducts()
                            ->with(['vendor.currency', 'variations'])
                            ->orderBy('price')
                            ->first();
                    @endphp
                    
                    @if($relatedVendorProduct)
                        @php
                            $hasVariations = $relatedVendorProduct->has_variations && $relatedVendorProduct->variations->count() > 0;
                            $relatedCurrencyCode = $relatedVendorProduct->vendor->currency->code ?? 'USD';
                            
                            if ($hasVariations) {
                                $minPrice = $relatedVendorProduct->variations->min('price');
                                $maxPrice = $relatedVendorProduct->variations->max('price');
                                $priceDisplay = $minPrice == $maxPrice 
                                    ? number_format($minPrice, 2)
                                    : number_format($minPrice, 2) . ' - ' . number_format($maxPrice, 2);
                                $inStock = $relatedVendorProduct->variations->sum('stock') > 0;
                            } else {
                                $price = (float) ($relatedVendorProduct->sale_price ?: $relatedVendorProduct->price ?: 0);
                                $priceDisplay = number_format($price, 2);
                                $inStock = ($relatedVendorProduct->stock ?? 0) > 0;
                            }
                            
                            $productImage = isset($sim->images[0]) 
                                ? url('uploads/' . $sim->images[0]) 
                                : url('uploads/no.jpg');
                        @endphp
                        
                        <a href="{{ route('product-show', [$sim->slug, $relatedVendorProduct->id]) }}" 
                           class="group bg-white rounded-xl overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="relative">
                                <img src="{{ $productImage }}" class="w-full h-40 object-cover group-hover:scale-105 transition duration-300">
                                @if(!$inStock)
                                    <div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full shadow">
                                        Rupture
                                    </div>
                                @endif
                            </div>
                            <div class="p-3">
                                <h3 class="text-sm font-medium text-gray-800 line-clamp-2 mb-2 group-hover:text-[#D4AF37] transition">
                                    {{ $sim->name }}
                                </h3>
                                <div class="flex items-baseline justify-between">
                                    <div>
                                        <span class="text-lg font-bold text-[#D4AF37]">{{ $priceDisplay }}</span>
                                        <span class="text-xs text-gray-500 ml-1">{{ $relatedCurrencyCode }}</span>
                                    </div>
                                </div>
                                @if($hasVariations)
                                    <p class="text-xs text-gray-400 mt-1 flex items-center">
                                        <i class="fas fa-tags mr-1"></i>
                                        {{ $relatedVendorProduct->variations->count() }} variantes
                                    </p>
                                @endif
                                <p class="text-xs text-gray-500 mt-1 flex items-center">
                                    <i class="fas fa-store mr-1"></i>
                                    {{ $relatedVendorProduct->vendor->store_name }}
                                </p>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- REVIEWS SECTION -->
        <div class="mt-8 bg-white rounded-2xl shadow-xl p-6 lg:p-8">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-star text-[#D4AF37] mr-3"></i>
                Avis des clients ({{ $totalReviews }})
            </h2>

            <!-- Review Form -->
            @if(auth()->check())
                @if(!$hasReviewed && !$editingReview)
                    <div class="bg-gray-50 rounded-xl p-6 mb-8 border border-gray-200">
                        <h3 class="font-semibold text-gray-800 mb-4">Laisser un avis</h3>
                        
                        <div class="flex gap-1 text-2xl mb-4">
                            @for ($i = 1; $i <= 5; $i++)
                                <button wire:click="$set('rating', {{ $i }})" type="button"
                                        class="focus:outline-none transform hover:scale-110 transition">
                                    <i class="fa-solid fa-star {{ $rating >= $i ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                </button>
                            @endfor
                        </div>

                        <textarea wire:model="comment" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent mb-4"
                            placeholder="Partagez votre expérience avec ce produit..."></textarea>

                        <button wire:click="submitReview" 
                                class="bg-[#D4AF37] hover:bg-[#c9a12f] text-white px-6 py-3 rounded-xl font-semibold transition">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Envoyer l'avis
                        </button>

                        @if($verifiedPurchase)
                            <p class="text-sm text-green-600 mt-3 flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                Achat vérifié
                            </p>
                        @endif
                    </div>
                @endif

                @if($editingReview)
                    <div class="bg-yellow-50 rounded-xl p-6 mb-8 border border-yellow-200">
                        <h3 class="font-semibold text-gray-800 mb-4">Modifier votre avis</h3>
                        
                        <div class="flex gap-1 text-2xl mb-4">
                            @for ($i = 1; $i <= 5; $i++)
                                <button wire:click="$set('rating', {{ $i }})" type="button"
                                        class="focus:outline-none transform hover:scale-110 transition">
                                    <i class="fa-solid fa-star {{ $rating >= $i ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                </button>
                            @endfor
                        </div>

                        <textarea wire:model="comment" rows="3"
                            class="w-full px-4 py-3 border border-yellow-200 rounded-xl focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent mb-4"></textarea>

                        <div class="flex gap-3">
                            <button wire:click="updateReview" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition">
                                <i class="fas fa-save mr-2"></i>
                                Mettre à jour
                            </button>
                            <button wire:click="cancelEditReview" 
                                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-xl font-semibold transition">
                                Annuler
                            </button>
                        </div>
                    </div>
                @endif

                @if($hasReviewed && !$editingReview)
                    <div class="bg-green-50 rounded-xl p-4 mb-8 flex items-center justify-between border border-green-200">
                        <p class="text-green-700 flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            Vous avez déjà laissé un avis pour ce produit.
                        </p>
                        <button wire:click="editReview" 
                                class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            <i class="fas fa-edit mr-1"></i>
                            Modifier
                        </button>
                    </div>
                @endif
            @else
                <div class="bg-blue-50 rounded-xl p-6 mb-8 border border-blue-200 text-center">
                    <i class="fas fa-lock text-blue-400 text-2xl mb-2"></i>
                    <p class="text-blue-700">
                        <a href="{{ route('customer_login') }}" class="font-semibold underline hover:text-blue-800">Connectez-vous</a> 
                        pour laisser un avis.
                    </p>
                </div>
            @endif

            <!-- REVIEWS LIST -->
            @if($reviews->count() > 0)
                <div class="space-y-6">
                    @foreach ($reviews as $rev)
                        <div class="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    @if($rev->user->avatar)
                                        <img src="{{ asset($rev->user->avatar) }}" class="w-10 h-10 rounded-full">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-600">
                                                {{ substr($rev->user->name, 0, 1) }}
                                            </span>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $rev->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $rev->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <div class="flex text-yellow-400">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star {{ $rev->rating >= $i ? '' : 'opacity-30' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            <p class="text-gray-700">{{ $rev->comment }}</p>
                            @if(\App\Models\OrderItem::where('product_id', $product->id)
                                ->whereHas('order', fn($q) => $q->where('user_id', $rev->user_id))
                                ->exists())
                                <p class="text-sm text-green-600 mt-2 flex items-center">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Achat vérifié
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-comment-dots text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500">Aucun avis pour le moment. Soyez le premier à donner votre avis !</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Lightbox Modal -->
    <div x-data="{ openLightbox: false, lightboxImage: '' }" 
         x-show="openLightbox" 
         @keydown.escape.window="openLightbox = false"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="background-color: rgba(0,0,0,0.95)" 
         x-cloak>
        <div class="flex items-center justify-center min-h-screen p-4">
            <button @click="openLightbox = false" 
                    class="absolute top-4 right-4 text-white hover:text-gray-300 text-4xl w-12 h-12 flex items-center justify-center rounded-full bg-black/50 hover:bg-black/70 transition z-10">
                <i class="fas fa-times"></i>
            </button>
            <button @click="lightboxImage = previousImage" 
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 text-4xl w-12 h-12 flex items-center justify-center rounded-full bg-black/50 hover:bg-black/70 transition"
                    x-show="hasPrevious">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button @click="lightboxImage = nextImage" 
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 text-4xl w-12 h-12 flex items-center justify-center rounded-full bg-black/50 hover:bg-black/70 transition"
                    x-show="hasNext">
                <i class="fas fa-chevron-right"></i>
            </button>
            <img :src="lightboxImage" class="max-w-full max-h-[90vh] object-contain rounded-lg">
        </div>
    </div>
</div>

<!-- Add this CSS for better scrollbar and animations -->
<style>
    [x-cloak] { display: none !important; }
    
    .scrollbar-thin::-webkit-scrollbar {
        height: 6px;
    }
    .scrollbar-thin::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .scrollbar-thin::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 10px;
    }
    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<script>
    function openLightboxImage(imageUrl) {
        const lightbox = document.querySelector('[x-data*="openLightbox"]');
        if (lightbox && lightbox.__x) {
            lightbox.__x.$data.openLightbox = true;
            lightbox.__x.$data.lightboxImage = imageUrl;
        }
    }
</script>