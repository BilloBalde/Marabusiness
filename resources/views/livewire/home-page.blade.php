<div class="bg-gray-50">

    {{-- ========================= --}}
    {{-- 4. BIG PROMO BANNER --}}
    {{-- ========================= --}}
    <section class="mt-3">
        <img src="{{ asset('assets/images/big banner.png') }}"
             class="w-full rounded-lg shadow">
    </section>

    {{-- ========================= --}}
    {{-- 5. CATEGORIES CAROUSEL --}}
    {{-- ========================= --}}
    @if($categories->count() > 0)
    <section class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Shop by Category</h2>
            <a href="/products" class="text-[#D4AF37] hover:text-[#c9a12f] font-medium">
                View All →
            </a>
        </div>
        
        <div class="relative">
            <div class="overflow-x-auto pb-4">
                <div class="flex space-x-6 min-w-min">
                    @foreach($categories as $category)
                        <a href="/products?selectedCategories[]={{ $category->id }}" 
                        class="min-w-[200px] bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                            <div class="relative h-40 overflow-hidden">
                                @if($category->products->count() > 0 && !empty($category->products[0]->images))
                                    <img src="{{ url('uploads/' . $category->products[0]->images[0]) }}" 
                                        alt="{{ $category->name }}"
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full bg-gradient-to-r from-gray-200 to-gray-300 flex items-center justify-center">
                                        <i class="fas fa-box-open text-gray-400 text-4xl"></i>
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                                <div class="absolute bottom-3 left-3 right-3">
                                    <h3 class="text-white font-bold text-lg">{{ $category->name }}</h3>
                                    @if($category->products->count() > 0)
                                        <p class="text-gray-200 text-sm">{{ $category->products->count() }} products</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ========================= --}}
    {{-- 6. FLASH DEALS BAR (GOLD) --}}
    {{-- ========================= --}}
    <div class="bg-[#D4AF37] text-white py-3 mt-5">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <span class="font-semibold text-lg">🔥 Ventes Flash</span>
            <span class="font-semibold">Expire dans</span>
            @if($saleProducts->count() > 0)
            @php
                // Get all sale_end dates that are not null
                $saleEnds = $saleProducts
                    ->pluck('sale_end')
                    ->filter()
                    ->values();
                
                
                if($saleEnds->count() > 0) {
                    // If you want the earliest expiration (soonest)
                    $soonestSaleEnd = $saleEnds->sort()->first();
                    
                    // If you want the latest expiration (furthest)
                    $latestSaleEnd = $saleEnds->sort()->last();
                    
                    // Calculate time remaining for the soonest expiration
                    $timeRemaining = \Carbon\Carbon::parse($soonestSaleEnd)
                        ->diffForHumans([
                            'parts' => 2,
                            'short' => false,
                        ]);
                }
            @endphp
            
            @if(isset($timeRemaining))
                <span class="font-semibold">{{ $timeRemaining }}</span>
            @else
                <span class="font-semibold">Promotions en cours</span>
            @endif
        @else
            <span class="font-semibold">Aucune promotion en cours</span>
        @endif
        </div>
    </div>

    {{-- ========================= --}}
    {{-- 7. FLASH SALE ITEMS --}}
    {{-- ========================= --}}
    <div class="max-w-7xl mx-auto px-4 py-4 overflow-x-auto flex space-x-4 no-scrollbar">

        @foreach($saleProducts as $product)
            <div class="min-w-[160px] bg-white p-3 rounded-lg shadow hover:shadow-xl transition relative group">

                {{-- IMAGE + HOVER DISCOUNT BADGE --}}
                <div class="relative">
                    <img src="{{ !empty($product->images) ? url('uploads/' . $product->images[0]) : 'https://via.placeholder.com/400x400' }}"
                         class="w-full h-28 object-cover rounded">

                    {{-- DISCOUNT BADGE — ONLY ON HOVER --}}
                    @if($product->sale_price)
                        <span class="
                            absolute top-2 right-2
                            bg-[#D4AF37] text-white text-xs font-bold
                            px-2 py-1 rounded-md
                            opacity-0 group-hover:opacity-100
                            transition-all duration-200
                        ">
                            -{{ $product->discount }}%
                        </span>
                    @endif
                    {{ $product->sale_end }}
                </div>

                <h3 class="text-sm mt-2 font-semibold">{{ $product->name }}</h3>

                {{-- PRICE --}}
                <p class="text-[#D4AF37] font-bold mt-1">
                    {{ number_format($product->display_price, 2) }} {{ $product->vendor_currency }}
                </p>

                {{-- OLD PRICE --}}
                @if($product->sale_price)
                    <p class="line-through text-gray-400 text-xs">
                        {{ number_format($product->original_price, 2) }} {{ $product->vendor_currency }}
                    </p>
                @endif

            </div>
        @endforeach

    </div>

    {{-- ========================= --}}
    {{-- 8. FEATURED PRODUCTS --}}
    {{-- ========================= --}}
    <section class="max-w-7xl mx-auto px-4 py-12">

        <h2 class="text-2xl font-bold mb-6">Recommandé pour vous</h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">

            @foreach($featuredProducts as $product)

                @php
                    $img = !empty($product->images)
                        ? url('uploads/' . $product->images[0])
                        : 'https://via.placeholder.com/400x400';

                    $price = number_format($product->display_price, 2);
                    $oldPrice = $product->original_price ? number_format($product->original_price, 2) : null;
                    $currency = $product->vendor_currency ?? 'USD';
                @endphp

                @if ($price > 0)
                <div class="bg-white p-4 rounded-xl shadow hover:shadow-2xl transition cursor-pointer group relative"
                     x-data="{ added: false }">

                    {{-- IMAGE + DISCOUNT BADGE --}}
                    @if($product->vendor_product_id)
                    <a href="/products/{{ $product->slug }}/{{ $product->vendor_product_id }}" class="relative block">
                        <img src="{{ $img }}" class="w-full h-48 object-cover rounded-lg">

                        @if($product->discount)
                            <span class="
                                absolute top-2 right-2
                                bg-[#D4AF37] text-white text-xs font-bold
                                px-2 py-1 rounded-md
                                opacity-0 group-hover:opacity-100
                                transition-all duration-200
                            ">
                                -{{ $product->discount }}%
                            </span>
                        @endif
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

                        {{-- CART BUTTON --}}
                        @if ($product->stock > 0 && $product->vendor_product_id)
                        <button
                            wire:click="addToCart({{ $product->vendor_product_id }})"
                            @click="added = true"
                            class="p-2 rounded-full transition bg-white border border-[#D4AF37] hover:bg-[#D4AF37] hover:text-white"
                        >
                            <i class="fa-solid fa-cart-shopping text-[#D4AF37] group-hover:text-white" x-show="!added"></i>
                            <i class="fa-solid fa-check text-green-500" x-show="added"></i>
                        </button>
                        @else
                        <button disabled class="p-2 rounded-full border bg-gray-300 cursor-not-allowed">
                            <i class="fa-solid fa-ban text-gray-500"></i>
                        </button>
                        @endif
                    </div>

                    {{-- RATING --}}
                    <p class="text-xs text-gray-500 mt-1">
                        ⭐ {{ rand(40,49) / 10 }} • {{ rand(50,8000) }} vendus
                    </p>

                    {{-- VENDOR NAME --}}
                    @if($product->vendor_name)
                        <p class="text-[10px] text-gray-400 mt-1">
                            Vendu par
                            <span class="font-medium">
                                <a href="/vendor/{{ $product->vendor_slug ?? '#' }}"
                                   class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-wide
                                          text-gray-900 rounded-full border border-gray-300
                                          hover:bg-gray-900 hover:text-white transition">
                                    {{ $product->vendor_name }} - Stock: {{ $product->stock }} Pcs
                                </a>
                            </span>
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
                <div class="overflow-x-auto pb-6">
                    <div class="flex space-x-6 min-w-min">
                        @foreach($vendors as $vendor)
                            <a href="/vendor/{{ $vendor->slug }}" 
                            class="min-w-[250px] bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group border border-gray-100">
                                <div class="p-6">
                                    <div class="flex items-center space-x-4 mb-4">
                                        @if($vendor->logo_path)
                                            <img src="{{ asset($vendor->logo_path) }}" 
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
                                    
                                    <p class="text-gray-600 text-sm line-clamp-2 mb-4">
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
                <div class="overflow-x-auto pb-6">
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

</div>

{{-- Add custom scrollbar hiding --}}
<style>
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
</style>