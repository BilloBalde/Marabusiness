<div>{{-- livewire-root : Livewire n'accepte qu'un seul element racine --}}
<div class="bg-gray-50">
    <section class="max-w-7xl mx-auto px-4 py-12">   
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- LEFT: VERTICAL CATEGORIES (3/12 = 25%) --}}
            <div class="lg:col-span-1">
                <h3 class="font-bold text-lg text-gray-800 mb-4">Popular Categories</h3>
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
                                    <p class="text-sm text-gray-500">{{ $category->products->count() }} products</p>
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
                            View All Products
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
                                            {{ $bannerTitle !== '' ? $bannerTitle : 'New Arrivals' }}
                                        </h3>
                                        <p class="text-lg mb-4 text-white/90">
                                            {{ $bannerDescription !== '' ? $bannerDescription : 'Discover the latest trends' }}
                                        </p>
                                        <a href="" 
                                        class="inline-block bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 transform hover:scale-105">
                                            Shop Now
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
                    <div class="relative bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] rounded-xl shadow-md overflow-hidden group promo-banner hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="p-6 text-white">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 rounded-full bg-black/20 flex items-center justify-center mr-4">
                                    <i class="fas fa-gift text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold">Limited Time Offer</h4>
                                    <p class="text-white/90">Buy 1 Get 1 Free</p>
                                </div>
                            </div>
                            <p class="mb-4 text-white/90">On selected items. Hurry, offer ends soon!</p>
                            <a href="/promo" 
                               class="inline-block bg-black hover:bg-gray-900 text-white font-semibold px-5 py-2.5 rounded-lg transition-all duration-300 transform hover:scale-105 border border-white/20">
                                Claim Offer <i class="fas fa-arrow-right ml-2"></i>
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
                    <div class="relative bg-gradient-to-r from-gray-900 to-black rounded-xl shadow-md overflow-hidden group promo-banner hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="p-6 text-white">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 rounded-full bg-[#D4AF37] flex items-center justify-center mr-4">
                                    <i class="fas fa-user-plus text-xl text-white"></i>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold text-white">New Customer Deal</h4>
                                    <p class="text-white/80">Exclusive Welcome Offer</p>
                                </div>
                            </div>
                            <p class="mb-4 text-white/70">Get 20% off your first order. Use code: WELCOME20</p>
                            <a href="/new-customer" 
                               class="inline-block bg-[#D4AF37] hover:bg-[#c9a12f] text-white font-semibold px-5 py-2.5 rounded-lg transition-all duration-300 transform hover:scale-105 border border-[#D4AF37]/30">
                                Get Discount <i class="fas fa-arrow-right ml-2"></i>
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
                View All Products →
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
                     x-data="{ added: false, loading: false, showQuickView: false }">
                    
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
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center">
                            <button @click="showQuickView = true" 
                                    class="bg-white text-gray-900 px-4 py-2 rounded-lg font-semibold hover:bg-[#D4AF37] hover:text-white transition-all transform translate-y-4 group-hover:translate-y-0">
                                Quick View
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
                            Stock: {{ $product->stock }} Pcs
                        </p>
                    @else
                        <p class="text-[10px] text-gray-400 mt-1">
                            <span class="font-medium">No Stock!</span>
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
            <h3 class="text-lg font-semibold text-gray-700 mb-2">No products available</h3>
            <p class="text-gray-500">Check back later for new arrivals</p>
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
                <h2 class="text-2xl font-bold text-gray-800">Our Trusted Vendors</h2>
                <a href="/vendors" class="text-[#D4AF37] hover:text-[#c9a12f] font-medium">
                    View All Vendors →
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
                                                    <i class="fas fa-check-circle mr-1"></i> Verified
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <p class="text-gray-600 text-sm line-clamp-2 mb-4 break-words whitespace-normal">
                                        {{ $vendor->description ?? 'Premium vendor on MARA BUSINESS' }}
                                    </p>
                                    
                                    <div class="flex items-center justify-between text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <i class="fas fa-star text-yellow-400 mr-1"></i>
                                            <span>{{ number_format($vendor->vendor_rating ?? 4.5, 1) }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-users mr-1"></i>
                                            <span>{{ $vendor->followers_count ?? 0 }} followers</span>
                                        </div>
                                    </div>
                                    
                                    {{-- Product Count --}}
                                    <div class="mt-2 text-sm text-gray-500 flex items-center">
                                        <i class="fas fa-box mr-1"></i>
                                        {{ $vendor->products_count ?? 0 }} products
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
                <h2 class="text-2xl font-bold text-gray-800">Our Services</h2>
                <a href="/services" class="text-[#D4AF37] hover:text-[#c9a12f] font-medium">
                    View All Services →
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
                                        {{ $service->description ?? 'Premium service offering' }}
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
    {{-- 11. NEWSLETTER SECTION --}}
    {{-- ========================= --}}
    <section class="bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] py-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Subscribe to Our Newsletter</h2>
            <p class="text-white/90 mb-6">Get the latest updates on new products and upcoming sales</p>
            <form class="max-w-md mx-auto flex flex-col sm:flex-row gap-2">
                <input type="email" 
                       placeholder="Your email address" 
                       class="flex-1 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-white">
                <button type="submit" 
                        class="px-6 py-3 bg-white text-[#D4AF37] font-semibold rounded-lg hover:bg-gray-100 transition-all hover:shadow-lg">
                    Subscribe
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
<div x-data="{ showQuickView: false, product: null }"
     x-show="showQuickView"
     @keydown.escape.window="showQuickView = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="showQuickView = false"></div>
        <div class="relative bg-white rounded-2xl max-w-2xl w-full p-6">
            <button @click="showQuickView = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <img :src="product?.image" class="w-full h-64 object-cover rounded-lg">
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2" x-text="product?.name"></h3>
                    <p class="text-[#D4AF37] font-bold text-2xl mb-4" x-text="product?.price"></p>
                    <p class="text-gray-600 mb-4" x-text="product?.description"></p>
                    <a :href="product?.url" class="inline-block bg-[#D4AF37] hover:bg-[#c9a12f] text-white px-6 py-3 rounded-lg font-semibold">
                        View Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
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
