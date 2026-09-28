<div>{{-- livewire-root : Livewire n'accepte qu'un seul element racine --}}
<div class="bg-gray-50">
    {{-- ========================= --}}
    {{-- 0. HERO CAROUSEL --}}
    {{-- ========================= --}}
    @if($heroSlides->count() > 0)
    <section class="relative">
        <div class="swiper heroSwiper">
            <div class="swiper-wrapper">
                @foreach($heroSlides as $slide)
                    <div class="swiper-slide">
                        <div class="relative h-[420px] md:h-[520px]">
                            <img src="{{ url('uploads/' . $slide->image) }}"
                                 alt="{{ $slide->title_text }}"
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/50"></div>
                            <div class="absolute inset-0 flex items-center">
                                <div class="max-w-7xl mx-auto px-4 w-full">
                                    <div class="max-w-xl text-white">
                                        <h2 class="text-3xl md:text-4xl font-bold mb-4">{{ $slide->title_text }}</h2>
                                        @if($slide->subtitle_text)
                                            <p class="text-lg md:text-xl text-white/90 mb-6">{{ $slide->subtitle_text }}</p>
                                        @endif
                                        @if($slide->cta_url)
                                            <a href="{{ $slide->cta_url }}"
                                               class="inline-block bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 transform hover:scale-105">
                                                {{ $slide->cta_label_text ?: 'En savoir plus' }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="hero-swiper-pagination absolute bottom-4 left-0 w-full flex justify-center gap-2 z-10"></div>
        </div>
    </section>
    @endif

    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- LEFT: VERTICAL CATEGORIES (3/12 = 25%) --}}
            <div class="lg:col-span-1">
                <h3 class="font-bold text-lg text-gray-800 mb-4">Catégories populaires</h3>
                @if($categories->count() > 0)
                <div class="space-y-4">
                    @foreach($categories->take(5) as $category)
                        <a href="/products?selectedCategories[]={{ $category->id }}" 
                        class="flex items-center space-x-4 p-3 rounded-lg hover:bg-gray-50 transition-all duration-300 group">
                            <div class="w-12 h-12 flex-shrink-0 rounded-lg overflow-hidden bg-gradient-to-r from-gray-100 to-gray-200">
                                @if($category->products->count() > 0 && !empty($category->products[0]->images))
                                    <img src="{{ url('uploads/' . $category->products[0]->images[0]) }}" 
                                        alt="{{ $category->name }}"
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                        onerror="this.onerror=null; this.src='{{ url('uploads/default.png') }}'">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                        <i class="fas fa-folder-open text-gray-400 text-xl"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-gray-800 truncate">{{ $category->name }}</h4>
                                @if($category->products->count() > 0)
                                    <p class="text-sm text-gray-500">{{ $category->products->count() }} produit(s)</p>
                                @endif
                            </div>
                            <div class="text-gray-400 group-hover:text-[#D4AF37] transition-colors">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
                
                {{-- View All Button --}}
                @if($categories->count() > 5)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <a href="/products" 
                        class="w-full py-3 px-4 bg-gray-50 hover:bg-[#D4AF37] text-gray-800 hover:text-white rounded-lg font-medium flex items-center justify-center transition-all duration-300 group">
                            Voir tous les produits
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                @endif
                @endif
            </div>
            
            {{-- RIGHT: BANNER CAROUSEL (9/12 = 75%) --}}
            <div class="lg:col-span-2">
                <div class="relative bg-white rounded-xl shadow-lg overflow-hidden">
                    {{-- Banner Carousel with Custom Controls --}}
                    <div class="swiper bannerSwiper">
                        <div class="swiper-wrapper">
                            @foreach ($banners as $banner)
                            <div class="swiper-slide">
                                <div class="relative">
                                    <img src="{{ asset('uploads/' . $banner->value) }}" 
                                        alt="Banner 1"
                                        class="w-full h-[400px] object-cover">
                                    <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-transparent"></div>
                                    @php
                                        $bannerTitle = trim((string) ($banner->label ?? ''));
                                        $bannerDescription = trim((string) ($banner->description ?? ''));
                                    @endphp
                                    <div class="absolute bottom-10 left-10 z-20 text-white max-w-lg rounded-xl bg-black/30 p-5 backdrop-blur-sm">
                                        <h3 class="text-3xl font-bold mb-2">
                                            {{ $bannerTitle !== '' ? $bannerTitle : 'Nouveautés' }}
                                        </h3>
                                        <p class="text-lg mb-4 text-white/90">
                                            {{ $bannerDescription !== '' ? $bannerDescription : 'Découvrez les dernières tendances' }}
                                        </p>
                                        {{-- href="" used to point nowhere but the current page — every
                                             banner's CTA silently reloaded the home page instead of
                                             taking anyone to the products it was advertising. --}}
                                        <a href="/products"
                                        class="inline-block bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 transform hover:scale-105">
                                            Acheter maintenant
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        {{-- Custom Navigation Controls --}}
                        <div class="absolute bottom-4 right-4 z-30 flex gap-2">
                            <button class="swiper-button-prev-custom w-10 h-10 bg-white/90 rounded-full flex items-center justify-center text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white transition-all">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="swiper-button-next-custom w-10 h-10 bg-white/90 rounded-full flex items-center justify-center text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white transition-all">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        {{-- Default Navigation Buttons --}}
                        {{-- <div class="swiper-button-next !text-white !w-12 !h-12 !bg-black/30 hover:!bg-black/50 !rounded-full !right-4"></div>
                        <div class="swiper-button-prev !text-white !w-12 !h-12 !bg-black/30 hover:!bg-black/50 !rounded-full !left-4"></div> --}}
                        
                        {{-- Pagination --}}
                        <div class="swiper-pagination !bottom-4"></div>
                    </div>
                </div>
                
                {{-- Improved Small Promo Banners Below --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    {{-- Gold & Black Banner --}}
                    {{--
                        Was "Limited Time Offer / Buy 1 Get 1 Free", linking to /promo — a
                        route that doesn't exist (404) advertising a BOGO promotion with no
                        mechanism anywhere in checkout to actually honor it. Repointed at the
                        real on_sale filter ProductsPage already supports (see its #[Url]
                        $on_sale) so this card now advertises actual discounted products
                        instead of a promise nothing in the system keeps.
                    --}}
                    <div class="relative bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] rounded-xl shadow-md overflow-hidden group promo-banner hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="p-6 text-white">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 rounded-full bg-black/20 flex items-center justify-center mr-4">
                                    <i class="fas fa-gift text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold">Offres en cours</h4>
                                    <p class="text-white/90">Articles en promotion</p>
                                </div>
                            </div>
                            <p class="mb-4 text-white/90">Découvrez les produits actuellement en solde chez nos vendeurs.</p>
                            <a href="/products?on_sale=1"
                               class="inline-block bg-black hover:bg-gray-900 text-white font-semibold px-5 py-2.5 rounded-lg transition-all duration-300 transform hover:scale-105 border border-white/20">
                                Voir les promos <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                        {{-- Pattern overlay --}}
                        <div class="absolute right-0 top-0 h-full w-1/3 opacity-10">
                            <div class="h-full w-full bg-gradient-to-l from-black to-transparent"></div>
                        </div>
                        {{-- Decorative dots --}}
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 rounded-full bg-white/10"></div>
                    </div>

                    {{-- Black & Gold Banner --}}
                    {{--
                        Was "New Customer Deal / Get 20% off... code WELCOME20", linking to
                        /new-customer — also a 404, advertising a coupon code that exists
                        nowhere in the codebase (no coupons table, nothing in checkout reads
                        a code). Repointed at /register, a real route, with copy that doesn't
                        promise a discount the platform can't actually give.
                    --}}
                    <div class="relative bg-gradient-to-r from-gray-900 to-black rounded-xl shadow-md overflow-hidden group promo-banner hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="p-6 text-white">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 rounded-full bg-[#D4AF37] flex items-center justify-center mr-4">
                                    <i class="fas fa-user-plus text-xl text-white"></i>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold text-white">Nouveau ici ?</h4>
                                    <p class="text-white/80">Rejoignez MARA BUSINESS</p>
                                </div>
                            </div>
                            <p class="mb-4 text-white/70">Créez votre compte pour suivre vos commandes et discuter directement avec nos vendeurs.</p>
                            <a href="/register"
                               class="inline-block bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold px-5 py-2.5 rounded-lg transition-all duration-300 transform hover:scale-105 border border-[#D4AF37]/30">
                                Créer un compte <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                        {{-- Pattern overlay --}}
                        <div class="absolute right-0 top-0 h-full w-1/3 opacity-10">
                            <div class="h-full w-full bg-gradient-to-l from-[#D4AF37] to-transparent"></div>
                        </div>
                        {{-- Decorative lines --}}
                        <div class="absolute -bottom-4 -left-4 w-32 h-32 border-2 border-[#D4AF37]/20 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================= --}}
    {{-- 8. FEATURED PRODUCTS --}}
    {{-- ========================= --}}
    <section class="max-w-7xl mx-auto px-4 py-12">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Recommandé pour vous</h2>
            <a href="/products" class="text-[#D4AF37] hover:text-[#c9a12f] font-medium">
                Voir tous les produits →
            </a>
        </div>

        @if($featuredProducts->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
            @foreach($featuredProducts as $product)
                @php
                    // Was via.placeholder.com, a service that no longer exists.
                    $img = !empty($product->images)
                        ? url('uploads/' . $product->images[0])
                        : url('uploads/default.png');

                    $price = number_format($product->display_price, 2);
                    $oldPrice = $product->original_price ? number_format($product->original_price, 2) : null;
                    $currency = $product->vendor_currency ?? 'USD';
                    
                    // The rating is computed from the reviews in HomePage::render()
                    // and read off $product below; it used to be rand(40,49)/10.

                    // Check if product is new (within 7 days)
                    /* $isNew = $product->created_at && $product->created_at->diffInDays(now()) < 7; */
                @endphp

                @if ($price > 0)
                <div class="bg-white p-4 rounded-xl shadow hover:shadow-2xl transition cursor-pointer group relative"
                     x-data="{ added: false, loading: false }">

                    {{-- IMAGE WITH OVERLAYS --}}
                    @if($product->vendor_product_id)
                    <a href="/products/{{ $product->slug }}/{{ $product->vendor_product_id }}" class="relative block overflow-hidden rounded-lg">
                        <img src="{{ $img }}"
                             class="w-full h-48 object-cover group-hover:scale-110 transition-transform duration-500"
                             onerror="this.onerror=null; this.src='{{ url('uploads/default.png') }}'">

                        {{-- Discount Badge --}}
                        @if($product->discount)
                            <span class="absolute top-2 right-2 bg-[#D4AF37] text-white text-xs font-bold px-2 py-1 rounded-md opacity-0 group-hover:opacity-100 transition-all duration-200 z-10">
                                -{{ $product->discount }}%
                            </span>
                        @endif

                        {{-- New Badge --}}
                        {{-- @if($isNew)
                            <span class="absolute top-2 left-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-md z-10">
                                NEW
                            </span>
                        @endif --}}

                        {{-- Quick View Overlay --}}
                        {{--
                            Used to set showQuickView on this card's OWN x-data, a scope
                            the real modal (section 13, far below in the DOM) has no access
                            to — every click quietly turned on a variable nothing read, and
                            the modal's own showQuickView stayed false forever. A shared
                            Alpine store is what lets a click way up here open a modal that
                            lives outside this card's subtree.
                        --}}
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center">
                            <button @click.prevent.stop="$store.quickView.open({
                                        name: {{ \Illuminate\Support\Js::from($product->name) }},
                                        price: {{ \Illuminate\Support\Js::from($price . ' ' . $currency) }},
                                        image: {{ \Illuminate\Support\Js::from($img) }},
                                        description: {{ \Illuminate\Support\Js::from($product->short_description ?? '') }},
                                        url: {{ \Illuminate\Support\Js::from('/products/' . $product->slug . '/' . $product->vendor_product_id) }},
                                    })"
                                    class="bg-white text-gray-900 px-4 py-2 rounded-lg font-semibold hover:bg-[#D4AF37] hover:text-white transition-all transform translate-y-4 group-hover:translate-y-0">
                                Aperçu rapide
                            </button>
                        </div>
                    </a>
                    @endif

                    {{-- NAME --}}
                    <h3 class="text-sm mt-2 font-semibold line-clamp-2">{{ $product->name }}</h3>

                    {{-- PRICE + CART ICON --}}
                    <div class="flex justify-between items-center mt-2 relative">
                        {{-- PRICE --}}
                        <div>
                            <p class="text-[#D4AF37] font-bold text-lg">{{ $price }} {{ $currency }}</p>
                            @if($oldPrice)
                                <p class="line-through text-gray-400 text-xs">
                                    {{ $oldPrice }} {{ $currency }}
                                </p>
                            @endif
                        </div>

                        {{-- WISHLIST BUTTON --}}
                        @if ($product->stock > 0 && $product->vendor_product_id)
                        <button
                            wire:click="addToWishlist({{ $product->vendor_product_id }})"
                            @click="loading = true; setTimeout(() => { loading = false; added = true; setTimeout(() => added = false, 2000); }, 500)"
                            class="p-2 rounded-full transition bg-white border border-[#D4AF37] hover:bg-[#D4AF37] hover:text-white relative"
                            :disabled="loading"
                        >
                            <template x-if="!added && !loading">
                                <i class="fa-solid fa-heart text-[#D4AF37] group-hover:text-white"></i>
                            </template>
                            <template x-if="added && !loading">
                                <i class="fa-solid fa-check text-green-500"></i>
                            </template>
                            <template x-if="loading">
                                <i class="fa-solid fa-spinner fa-spin text-[#D4AF37]"></i>
                            </template>
                        </button>
                        @else
                        <button disabled class="p-2 rounded-full border bg-gray-300 cursor-not-allowed">
                            <i class="fa-solid fa-ban text-gray-500"></i>
                        </button>
                        @endif
                    </div>

                    {{-- RATING --}}
                    {{-- Both figures come from HomePage::render() now. The stars and
                         the sales count each appear only when there is something real
                         behind them; a product nobody has reviewed or bought simply
                         shows neither, instead of inventing both. --}}
                    @if($product->reviews_count > 0 || $product->sold_count > 0)
                        {{-- flex-wrap: the rating and the sales count together are wider
                             than a product card on a phone, and without it the "vendus"
                             text ran outside the card's right edge. --}}
                        <div class="flex flex-wrap items-center gap-x-1 text-xs text-gray-500 mt-1">
                            @if($product->reviews_count > 0)
                                @php
                                    $fullStars = floor($product->rating);
                                    $hasHalf = $product->rating - $fullStars >= 0.5;
                                @endphp
                                <div class="flex text-yellow-400">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $fullStars)
                                            <i class="fas fa-star"></i>
                                        @elseif($hasHalf && $i == $fullStars + 1)
                                            <i class="fas fa-star-half-alt"></i>
                                        @else
                                            <i class="far fa-star"></i>
                                        @endif
                                    @endfor
                                </div>
                                <span>{{ number_format($product->rating, 1) }}</span>
                                <span class="text-gray-400">({{ $product->reviews_count }})</span>
                            @endif

                            @if($product->reviews_count > 0 && $product->sold_count > 0)
                                <span>•</span>
                            @endif

                            @if($product->sold_count > 0)
                                <span>{{ number_format($product->sold_count) }} vendus</span>
                            @endif
                        </div>
                    @endif

                    {{-- VENDOR NAME WITH VERIFIED BADGE --}}
                    @if($product->vendor_name)
                        <p class="text-[10px] text-gray-400 mt-1 flex items-center">
                            Vendu par
                            <a href="/vendor/{{ $product->vendor_slug ?? '#' }}" 
                               class="ml-1 font-medium text-gray-600 hover:text-[#D4AF37] transition-colors flex items-center">
                                {{ $product->vendor_name }}
                                @if($product->vendor_is_verified ?? false)
                                    <i class="fas fa-check-circle text-blue-500 ml-1 text-[8px]"></i>
                                @endif
                            </a>
                        </p>
                        <p class="text-[10px] text-gray-400">
                            Stock : {{ $product->stock }} unités
                        </p>
                    @else
                        <p class="text-[10px] text-gray-400 mt-1">
                            <span class="font-medium">Rupture de stock</span>
                        </p>
                    @endif
                </div>
                @endif
            @endforeach
        </div>
        @else
        {{-- Empty State --}}
        <div class="col-span-full text-center py-12">
            <div class="inline-block p-6 bg-gray-100 rounded-full mb-4">
                <i class="fas fa-box-open text-gray-400 text-4xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Aucun produit disponible</h3>
            <p class="text-gray-500">Revenez bientôt pour découvrir nos nouveautés</p>
        </div>
        @endif
    </section>

    {{-- ========================= --}}
    {{-- 9. VENDORS CAROUSEL --}}
    {{-- ========================= --}}
    @if($vendors->count() > 0)
    <section class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Nos vendeurs de confiance</h2>
                <a href="/vendors" class="text-[#D4AF37] hover:text-[#c9a12f] font-medium">
                    Voir tous les vendeurs →
                </a>
            </div>
            
            <div class="relative">
                <div class="overflow-x-auto pb-6 no-scrollbar">
                    <div class="flex space-x-6 min-w-min">
                        @foreach($vendors as $vendor)
                            <a href="/vendor/{{ $vendor->slug }}" 
                            class="min-w-[250px] bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group border border-gray-100">
                                <div class="p-6">
                                    <div class="flex items-center space-x-4 mb-4">
                                        @if($vendor->logo_path)
                                            <img src="{{ asset('uploads/'.$vendor->logo_path) }}" 
                                                alt="{{ $vendor->store_name }}"
                                                class="w-16 h-16 rounded-full object-cover border-2 border-[#D4AF37]">
                                        @else
                                            <div class="w-16 h-16 rounded-full bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] flex items-center justify-center border-2 border-[#D4AF37]">
                                                <span class="text-white text-xl font-bold">
                                                    {{ substr($vendor->store_name, 0, 1) }}
                                                </span>
                                            </div>
                                        @endif
                                        <div>
                                            <h3 class="font-bold text-gray-900 group-hover:text-[#D4AF37] transition-colors">
                                                {{ $vendor->store_name }}
                                            </h3>
                                            @if($vendor->is_verified)
                                                <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                                    <i class="fas fa-check-circle mr-1"></i> Vérifié
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <p class="text-gray-600 text-sm line-clamp-2 mb-4 break-words whitespace-normal">
                                        {{ $vendor->description ?? 'Vendeur de confiance sur MARA BUSINESS' }}
                                    </p>

                                    <div class="flex items-center justify-between text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <i class="fas fa-star text-yellow-400 mr-1"></i>
                                            <span>{{ number_format($vendor->vendor_rating ?? 4.5, 1) }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-users mr-1"></i>
                                            <span>{{ $vendor->followers_count ?? 0 }} abonné(s)</span>
                                        </div>
                                    </div>

                                    {{-- Product Count --}}
                                    <div class="mt-2 text-sm text-gray-500 flex items-center">
                                        <i class="fas fa-box mr-1"></i>
                                        {{ $vendor->products_count ?? 0 }} produit(s)
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ========================= --}}
    {{-- 10. SERVICES CAROUSEL --}}
    {{-- ========================= --}}
    @if($services->count() > 0)
    <section class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Nos services</h2>
                <a href="/services" class="text-[#D4AF37] hover:text-[#c9a12f] font-medium">
                    Voir tous les services →
                </a>
            </div>
            
            <div class="relative">
                <div class="overflow-x-auto pb-6 no-scrollbar">
                    <div class="flex space-x-6 min-w-min">
                        @foreach($services as $service)
                            <a href="/service/{{ $service->slug }}" 
                            class="min-w-[200px] bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group border border-gray-200">
                                <div class="p-6 text-center">
                                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                        @if($service->icon)
                                            <i class="{{ $service->icon }} text-white text-2xl"></i>
                                        @else
                                            <i class="fas fa-concierge-bell text-white text-2xl"></i>
                                        @endif
                                    </div>
                                    
                                    <h3 class="font-bold text-gray-900 mb-2 group-hover:text-[#D4AF37] transition-colors">
                                        {{ $service->name }}
                                    </h3>
                                    
                                    <p class="text-gray-600 text-sm line-clamp-3">
                                        {{ $service->description ?? 'Service de qualité proposé par MARA BUSINESS' }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ========================= --}}
    {{-- 10b. SOURCING CARDS CAROUSEL --}}
    {{-- ========================= --}}
    @if($sourcings->count() > 0)
    <section class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Nos Sourcing</h2>
                <a href="{{ route('sourcing.index') }}" class="text-[#D4AF37] hover:text-[#c9a12f] font-medium">
                    Voir tout le sourcing →
                </a>
            </div>

            <div class="relative">
                <div class="overflow-x-auto pb-6 no-scrollbar">
                    <div class="flex space-x-6 min-w-min">
                        @foreach($sourcings as $sourcing)
                            <a href="{{ route('sourcing.show', $sourcing->slug) }}"
                               class="min-w-[260px] max-w-[260px] bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group border border-gray-100">
                                <div class="h-40 bg-gradient-to-br from-[#D4AF37] to-[#c9a12f] flex items-center justify-center">
                                    @if(!empty($sourcing->gallery[0]))
                                        <img src="{{ url('uploads/' . $sourcing->gallery[0]) }}"
                                             alt="{{ $sourcing->title_text }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <i class="fas fa-boxes-stacked text-white text-4xl"></i>
                                    @endif
                                </div>
                                <div class="p-5">
                                    <h3 class="font-bold text-gray-900 mb-2 group-hover:text-[#D4AF37] transition-colors">
                                        {{ $sourcing->title_text }}
                                    </h3>
                                    <p class="text-gray-600 text-sm line-clamp-2 mb-3">
                                        {{ $sourcing->short_description_text }}
                                    </p>
                                    <p class="text-xs text-gray-400">{{ $sourcing->source_products_count }} produit(s)</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ========================= --}}
    {{-- 10c. TESTIMONIALS CAROUSEL --}}
    {{-- ========================= --}}
    @if($testimonials->count() > 0)
    <section id="testimonials" class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-2xl font-bold text-gray-800 mb-8 text-center">Ce que nos clients disent</h2>

            <div class="relative">
                <div class="overflow-x-auto pb-6 no-scrollbar">
                    <div class="flex space-x-6 min-w-min">
                        @foreach($testimonials as $testimonial)
                            <div class="min-w-[300px] max-w-[300px] bg-white rounded-xl shadow-md p-6 border border-gray-100">
                                <div class="flex items-center mb-4">
                                    @if($testimonial->photo)
                                        <img src="{{ url('uploads/' . $testimonial->photo) }}"
                                             alt="{{ $testimonial->name }}"
                                             class="w-12 h-12 rounded-full object-cover mr-3">
                                    @else
                                        <div class="w-12 h-12 rounded-full bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] flex items-center justify-center mr-3">
                                            <span class="text-white font-bold">{{ substr($testimonial->name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $testimonial->name }}</p>
                                        @if($testimonial->role)
                                            <p class="text-xs text-gray-500">{{ $testimonial->role }}</p>
                                        @endif
                                    </div>
                                </div>
                                @if($testimonial->rating)
                                    <div class="flex text-yellow-400 mb-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="{{ $i <= $testimonial->rating ? 'fas' : 'far' }} fa-star text-sm"></i>
                                        @endfor
                                    </div>
                                @endif
                                <p class="text-gray-600 text-sm italic">« {{ $testimonial->message_text }} »</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ========================= --}}
    {{-- 10d. PARTNERS LOGOS --}}
    {{-- ========================= --}}
    @if($partners->count() > 0)
    <section class="bg-white py-10 border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4">
            <p class="text-center text-sm font-medium text-gray-400 uppercase tracking-wide mb-6">Ils nous font confiance</p>
            <div class="flex flex-wrap items-center justify-center gap-10">
                @foreach($partners as $partner)
                    @php $logo = '<img src="' . url('uploads/' . $partner->logo) . '" alt="' . $partner->name . '" class="h-10 object-contain opacity-70 hover:opacity-100 transition-opacity">'; @endphp
                    @if($partner->website)
                        <a href="{{ $partner->website }}" target="_blank" rel="noopener">{!! $logo !!}</a>
                    @else
                        {!! $logo !!}
                    @endif
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ========================= --}}
    {{-- 10e. CTA CONTACT --}}
    {{-- ========================= --}}
    <section class="bg-gray-900 py-14">
        <div class="max-w-3xl mx-auto px-4 text-center">
            <h2 class="text-2xl md:text-3xl font-bold text-white mb-3">Un projet à concrétiser ?</h2>
            <p class="text-gray-300 mb-6">Parlez-nous de votre besoin en sourcing ou en étude à l'étranger — notre équipe vous répond rapidement.</p>
            <a href="/contact"
               class="inline-block bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold px-8 py-3 rounded-lg transition-all duration-300 transform hover:scale-105">
                Contactez-nous
            </a>
        </div>
    </section>

    {{-- ========================= --}}
    {{-- 11. NEWSLETTER SECTION --}}
    {{-- ========================= --}}
    <section class="bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] py-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Abonnez-vous à notre newsletter</h2>
            <p class="text-white/90 mb-6">Recevez nos actualités, nouveaux produits et promotions</p>
            <form class="max-w-md mx-auto flex flex-col sm:flex-row gap-2">
                <input type="email"
                       placeholder="Votre adresse e-mail"
                       class="flex-1 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-white">
                <button type="submit"
                        class="px-6 py-3 bg-white text-[#D4AF37] font-semibold rounded-lg hover:bg-gray-100 transition-all hover:shadow-lg">
                    S'abonner
                </button>
            </form>
        </div>
    </section>
</div>

{{-- ========================= --}}
{{-- 12. SCROLL TO TOP BUTTON --}}
{{-- ========================= --}}
<button 
    x-data="{ show: false }"
    @scroll.window="show = window.scrollY > 500"
    @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
    x-show="show"
    x-transition
    class="fixed bottom-8 right-8 w-12 h-12 bg-[#D4AF37] hover:bg-[#c9a12f] text-white rounded-full shadow-lg flex items-center justify-center z-50 cursor-pointer"
    style="display: none;"
>
    <i class="fas fa-arrow-up"></i>
</button>

{{-- ========================= --}}
{{-- 13. QUICK VIEW MODAL --}}
{{-- ========================= --}}
{{--
    Reads from the global $store.quickView (registered in alpine:init below)
    instead of its own local x-data — every "Aperçu rapide" button lives inside
    its own product card's x-data further up the DOM, so a modal scoped only to
    itself could never be opened from there.
--}}
<div x-data
     x-show="$store.quickView.visible"
     @keydown.escape.window="$store.quickView.close()"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$store.quickView.close()"></div>
        <div class="relative bg-white rounded-2xl max-w-2xl w-full p-6">
            <button @click="$store.quickView.close()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <img :src="$store.quickView.product?.image" class="w-full h-64 object-cover rounded-lg">
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2" x-text="$store.quickView.product?.name"></h3>
                    <p class="text-[#D4AF37] font-bold text-2xl mb-4" x-text="$store.quickView.product?.price"></p>
                    <p class="text-gray-600 mb-4" x-text="$store.quickView.product?.description"></p>
                    <a :href="$store.quickView.product?.url" class="inline-block bg-[#D4AF37] hover:bg-[#c9a12f] text-white px-6 py-3 rounded-lg font-semibold">
                        Voir le produit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    // The quickView store itself is registered in the main layout
    // (resources/views/components/layouts/app.blade.php), not here — Livewire
    // re-runs a component's own <script> tags later than Alpine's boot, too
    // late for an alpine:init listener added from inside one to ever fire.

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize banner swiper
        if (typeof Swiper !== 'undefined') {
            const bannerSwiper = new Swiper('.bannerSwiper', {
                loop: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                effect: 'fade',
                fadeEffect: {
                    crossFade: true
                },
                speed: 1000,
                on: {
                    init: function() {
                        console.log('Banner swiper initialized');
                    }
                }
            });
            
            // Custom navigation buttons
            document.querySelector('.swiper-button-next-custom')?.addEventListener('click', function() {
                bannerSwiper.slideNext();
            });
            
            document.querySelector('.swiper-button-prev-custom')?.addEventListener('click', function() {
                bannerSwiper.slidePrev();
            });
            
            // Pause on hover
            const swiperEl = document.querySelector('.bannerSwiper');
            if (swiperEl) {
                swiperEl.addEventListener('mouseenter', () => bannerSwiper.autoplay.stop());
                swiperEl.addEventListener('mouseleave', () => bannerSwiper.autoplay.start());
            }

            // Hero carousel — its own pagination element, kept separate from
            // .bannerSwiper's so the two instances never fight over one dot bar.
            const heroSwiperContainer = document.querySelector('.heroSwiper');
            if (heroSwiperContainer) {
                new Swiper('.heroSwiper', {
                    loop: true,
                    autoplay: {
                        delay: 6000,
                        disableOnInteraction: false,
                    },
                    pagination: {
                        el: '.hero-swiper-pagination',
                        clickable: true,
                    },
                    effect: 'fade',
                    fadeEffect: {
                        crossFade: true
                    },
                    speed: 1000,
                });
            }
        }
    });
</script>

<style>
    /* Custom scrollbar hiding */
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    /* Swiper navigation styling */
    .swiper-button-next, .swiper-button-prev {
        transition: all 0.3s ease;
    }
    .swiper-button-next:hover, .swiper-button-prev:hover {
        background-color: rgba(0, 0, 0, 0.5) !important;
    }
    .swiper-pagination-bullet {
        background: white !important;
        opacity: 0.5;
    }
    .swiper-pagination-bullet-active {
        opacity: 1;
        background: #D4AF37 !important;
    }
    
    /* Promo banners hover effect */
    .promo-banner {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .promo-banner:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }
    
    /* Line clamp utility */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
</div>
