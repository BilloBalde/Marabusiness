<div class="flex items-center gap-2">
    @foreach ($locales as $locale)
        <a href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}"
           class="px-2 py-1 rounded text-sm
                  {{ $current === $locale ? 'bg-primary-600 text-white' : 'bg-gray-200 hover:bg-gray-300' }}">
            {{ strtoupper($locale) }}
        </a>
    @endforeach
</div>
