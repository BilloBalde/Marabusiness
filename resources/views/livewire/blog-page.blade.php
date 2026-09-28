<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-7xl mx-auto px-6">
        @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.blog', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])

        <h1 class="text-3xl font-bold text-gray-800 mb-8">{{ __('ui.navbar.blog') }}</h1>

        @if ($posts->isEmpty())
            <p class="text-gray-500">Aucun article publié pour le moment.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($posts as $post)
                    <a wire:navigate href="{{ route('blog.show', $post->slug) }}"
                       class="group bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow flex flex-col">
                        <div class="h-44 bg-gray-100 overflow-hidden">
                            @if ($post->cover_image)
                                <img src="{{ url('uploads/' . $post->cover_image) }}" alt="{{ $post->title_text }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                            @endif
                        </div>
                        <div class="p-4 flex-1 flex flex-col">
                            @if ($post->category)
                                <span class="text-xs font-medium text-[#D4AF37] uppercase tracking-wide mb-1">{{ $post->category->name_text }}</span>
                            @endif
                            <h2 class="font-semibold text-gray-800 group-hover:text-[#D4AF37] mb-2">{{ $post->title_text }}</h2>
                            @if ($post->excerpt_text)
                                <p class="text-sm text-gray-500 line-clamp-3 flex-1">{{ $post->excerpt_text }}</p>
                            @endif
                            <span class="text-sm text-[#D4AF37] font-medium mt-3">Lire l'article →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
