<div class="relative" wire:click.away="$set('results', [])">

    {{-- SEARCH INPUT --}}
    <input type="text"
           wire:model.live="query"
           wire:keydown.enter="search"
           placeholder="Rechercher un produit..."
           class="w-full bg-gray-100 px-4 py-3 pr-12 rounded-full focus:ring-2 focus:ring-[#D4AF37]">

    {{-- SEARCH BUTTON --}}
    <button wire:click="search"
        class="absolute inset-y-0 right-0 flex items-center justify-center px-4
               bg-[#D4AF37] text-white rounded-r-full hover:bg-[#C9A227]">
        🔍
    </button>

    {{-- AUTOCOMPLETE DROPDOWN --}}
    @if(!empty($results))
        <div class="absolute left-0 right-0 bg-white border rounded-lg shadow-lg mt-2 z-50">

            @foreach($results as $product)
                @php
                    // Was via.placeholder.com, a service that no longer exists.
                    $img = isset($product->images[0])
                        ? url('uploads/' . $product->images[0])
                        : url('uploads/default.png');
                @endphp

                <a href="/products/{{ $product->slug }}/{{ $product->vendorProducts[0]->id }}"
                   class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100">

                    <img src="{{ $img }}" class="w-10 h-10 rounded object-cover">

                    <span class="text-sm text-gray-700">{{ $product->name }}</span>
                </a>
            @endforeach

            <button wire:click="search"
                    class="w-full px-4 py-2 text-sm text-center text-blue-600 hover:bg-gray-50">
                Voir tous les résultats
            </button>

        </div>
    @endif

</div>
