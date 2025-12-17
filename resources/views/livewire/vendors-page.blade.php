<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-7xl mx-auto px-6">
        @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.vendors'])
        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            Boutiques Officielles
        </h1>

        <!-- 🔍 SEARCH BAR -->
        <div class="mb-6">
            <div class="relative">
                <input
                    type="text"
                    wire:model.defer="search"
                    wire:keydown.enter="searchNow"
                    class="w-full border rounded-lg px-4 py-2 shadow-sm focus:ring-[#D4AF37] focus:border-[#D4AF37]"
                    placeholder="Rechercher une boutique…">

                <button
                    wire:click="searchNow"
                    class="absolute inset-y-0 right-0 px-4 bg-[#D4AF37] text-white rounded-r-lg hover:bg-[#c9a12f]">
                    🔍
                </button>
            </div>
        </div>

        <!-- GRID VENDORS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach ($vendors as $vendor)
            <a href="{{ route('vendor.show', $vendor->slug) }}"
               class="bg-white rounded-xl shadow hover:shadow-lg transition block p-4">

                <!-- LOGO -->
                <div class="flex justify-center mb-3">
                    @if($vendor->logo_path)
                        <img src="{{ asset($vendor->logo_path) }}"
                             class="w-20 h-20 rounded-full border shadow object-cover">
                    @else
                        <div class="w-20 h-20 rounded-full bg-gray-200 border shadow flex items-center justify-center">
                            <span class="text-2xl font-bold text-gray-600">
                                {{ substr($vendor->store_name, 0, 1) }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- INFO -->
                <h3 class="font-semibold text-center text-gray-800">
                    {{ $vendor->store_name }}
                </h3>

                <p class="text-xs text-center text-gray-500 mt-1">
                    {{ $vendor->description ? Str::limit($vendor->description, 40) : 'Boutique Officielle' }}
                </p>

                <!-- STATS -->
                <div class="flex justify-center items-center gap-4 text-sm text-gray-600 mt-3">
                    <!-- Rating -->
                    <div class="flex items-center gap-1">
                        @php
                            $rating = $vendor->vendor_rating;
                            $fullStars = floor($rating);
                            $hasHalfStar = $rating - $fullStars >= 0.5;
                        @endphp
                        
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $fullStars)
                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                            @elseif($hasHalfStar && $i == $fullStars + 1)
                                <i class="fas fa-star-half-alt text-yellow-400 text-xs"></i>
                            @else
                                <i class="far fa-star text-gray-300 text-xs"></i>
                            @endif
                        @endfor
                        <span class="ml-1">{{ number_format($rating, 1) }}</span>
                    </div>
                    
                    <!-- Followers -->
                    <div class="flex items-center gap-1">
                        <i class="fas fa-users text-gray-400 text-xs"></i>
                        <span>{{ $vendor->followers_count }}</span>
                    </div>
                </div>

                <!-- Reviews Count -->
                @if($vendor->approved_vendor_reviews_count > 0)
                    <p class="text-xs text-center text-gray-500 mt-1">
                        {{ $vendor->approved_vendor_reviews_count }} review(s)
                    </p>
                @endif

                <!-- VIEW BUTTON -->
                <div class="mt-4 flex justify-center">
                    <span class="px-4 py-1 text-sm rounded-full text-white bg-[#D4AF37] hover:bg-[#c9a12f] shadow">
                        Voir Boutique
                    </span>
                </div>

            </a>
            @endforeach
        </div>

        <!-- PAGINATION -->
        <div class="mt-8">
            {{ $vendors->links() }}
        </div>
    </div>
</div>