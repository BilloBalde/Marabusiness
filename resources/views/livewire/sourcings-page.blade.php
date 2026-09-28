<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-7xl mx-auto px-6">
        @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.sourcing', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])

        <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ __('ui.navbar.sourcing') }}</h1>

        @if ($intro)
            <p class="text-gray-600 max-w-3xl mb-10">{{ $intro }}</p>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($sourcings as $sourcing)
                <a wire:navigate href="{{ route('sourcing.show', $sourcing->slug) }}"
                   class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="h-40 bg-gray-100 overflow-hidden">
                        @if (!empty($sourcing->gallery[0]))
                            <img src="{{ url('uploads/' . $sourcing->gallery[0]) }}" alt="{{ $sourcing->title_text }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                        @endif
                    </div>
                    <div class="p-4">
                        <h2 class="font-semibold text-gray-800 group-hover:text-[#D4AF37]">{{ $sourcing->title_text }}</h2>
                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $sourcing->short_description_text }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        @if ($sourcings->isEmpty())
            <p class="text-gray-500">{{ __('sourcing.none_yet') }}</p>
        @endif
    </div>
</div>
