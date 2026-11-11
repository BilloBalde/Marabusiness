<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-3xl mx-auto px-6">
        @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.blog', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])

        @if ($post->cover_image)
            <div class="h-64 md:h-80 rounded-xl overflow-hidden mb-6">
                <img src="{{ url('uploads/' . $post->cover_image) }}" alt="{{ $post->title_text }}"
                     class="w-full h-full object-cover">
            </div>
        @endif

        @if ($post->category)
            <span class="text-xs font-medium text-[#D4AF37] uppercase tracking-wide">{{ $post->category->name_text }}</span>
        @endif

        <h1 class="text-3xl font-bold text-gray-800 mt-2 mb-4">{{ $post->title_text }}</h1>

        <div class="flex items-center gap-3 text-sm text-gray-500 mb-8">
            @if ($post->author)
                <span>{{ $post->author->name }}</span>
                <span>·</span>
            @endif
            @if ($post->published_at)
                <span>{{ $post->published_at->format('d/m/Y') }}</span>
            @endif
        </div>

        <div class="prose max-w-none text-gray-700">
            {!! \App\Support\HtmlSanitizer::clean($post->content_text) !!}
        </div>

        @if (!empty($post->tags))
            <div class="flex flex-wrap gap-2 mt-8">
                @foreach ($post->tags as $tag)
                    <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">#{{ $tag }}</span>
                @endforeach
            </div>
        @endif

        <a wire:navigate href="{{ route('blog.index') }}" class="inline-block mt-10 text-[#D4AF37] hover:text-[#c9a12f] font-medium">
            ← Retour au blog
        </a>
    </div>
</div>
