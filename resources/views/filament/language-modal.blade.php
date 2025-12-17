<div class="space-y-4">
    @php
        $langs = ['en' => 'English', 'fr' => 'Français', 'zh' => '中文'];
        $current = app()->getLocale();
    @endphp

    @foreach ($langs as $code => $label)
        <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}"
            class="flex items-center justify-between p-3 rounded-lg border 
                   @if($current === $code) bg-primary-100 border-primary-400 @endif">
            <span>{{ $label }}</span>

            @if ($current === $code)
                <x-heroicon-o-check class="w-5 h-5 text-primary-600" />
            @endif
        </a>
    @endforeach
</div>
