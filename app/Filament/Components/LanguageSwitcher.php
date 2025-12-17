<?php

namespace App\Filament\Components;

use Filament\Support\Components\ViewComponent;
use Illuminate\Contracts\View\View;

class LanguageSwitcher extends ViewComponent
{
    public array $locales = ['en', 'fr', 'zh'];

    public function render(): View
    {
        return view('filament.components.language-switcher', [
            'locales' => $this->locales,
            'current' => app()->getLocale(),
        ]);
    }
}
