<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-5xl mx-auto px-6">
        @include('livewire.partials.nav-header', ['tileContent' => $sourcing->title_text, 'hasSub' => true, 'subContent' => 'ui.navbar.sourcing', 'subLink' => 'sourcing'])

        @if (session('success'))
            <div class="mb-6 p-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ session('success') }}</div>
        @endif

        {{-- HERO --}}
        <div class="rounded-xl overflow-hidden bg-white border border-gray-200 mb-8">
            @if (!empty($sourcing->gallery[0]))
                <div class="h-56 bg-gray-100">
                    <img src="{{ url('uploads/' . $sourcing->gallery[0]) }}" alt="{{ $sourcing->title_text }}" class="w-full h-full object-cover">
                </div>
            @endif
            <div class="p-6">
                <h1 class="text-3xl font-bold text-gray-800">{{ $sourcing->title_text }}</h1>
                <p class="text-gray-600 mt-2">{{ $sourcing->short_description_text }}</p>
            </div>
        </div>

        {{-- DESCRIPTION --}}
        @if ($sourcing->description_text)
            <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8 prose max-w-none">
                {!! $sourcing->description_text !!}
            </div>
        @endif

        {{-- GALLERY --}}
        @if (!empty($sourcing->gallery) && count($sourcing->gallery) > 1)
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">{{ __('sourcing.gallery') }}</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach ($sourcing->gallery as $image)
                        <img src="{{ url('uploads/' . $image) }}" class="w-full h-28 object-cover rounded-lg border border-gray-200">
                    @endforeach
                </div>
            </div>
        @endif

        {{-- LINKS + CATALOG --}}
        @if (!empty($sourcing->external_links) || $sourcing->catalog_pdf)
            <div class="flex flex-wrap gap-3 mb-8">
                @foreach ($sourcing->external_links ?? [] as $platform => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener"
                       class="px-4 py-2 rounded-full bg-white border border-gray-200 text-sm text-gray-700 hover:border-[#D4AF37] hover:text-[#D4AF37]">
                        {{ ucfirst($platform) }}
                    </a>
                @endforeach
                @if ($sourcing->catalog_pdf)
                    <a href="{{ url('uploads/' . $sourcing->catalog_pdf) }}" target="_blank"
                       class="px-4 py-2 rounded-full bg-[#D4AF37] text-white text-sm font-medium hover:bg-[#c9a12f]">
                        📄 {{ __('sourcing.download_catalog') }}
                    </a>
                @endif
            </div>
        @endif

        {{-- PRODUCTS --}}
        <div>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ __('sourcing.related_products') }}</h2>

            @if ($sourcing->sourceProducts->isEmpty())
                <p class="text-gray-500">{{ __('sourcing.no_products_yet') }}</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($sourcing->sourceProducts as $product)
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col">
                            <div class="h-36 bg-gray-100">
                                @if (!empty($product->images[0]))
                                    <img src="{{ url('uploads/' . $product->images[0]) }}" alt="{{ $product->name_text }}" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="p-4 flex-1 flex flex-col">
                                <h3 class="font-semibold text-gray-800">{{ $product->name_text }}</h3>
                                @if ($product->short_description_text)
                                    <p class="text-sm text-gray-500 mt-1 flex-1">{{ $product->short_description_text }}</p>
                                @endif
                                <div class="mt-3 text-sm text-gray-600 space-y-1">
                                    @if ($product->price_range)
                                        <p>💰 {{ $product->price_range }}</p>
                                    @endif
                                    @if ($product->quantity_minimum)
                                        <p>📦 MOQ {{ $product->quantity_minimum }}</p>
                                    @endif
                                </div>
                                <button wire:click="selectProduct({{ $product->id }})"
                                        class="mt-4 w-full px-4 py-2 rounded-lg bg-[#D4AF37] text-white font-medium hover:bg-[#c9a12f] transition">
                                    {{ __('sourcing.select') }}
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
