<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';

    public function getHeading(): string
    {
        return __('filament.nav.dashboard');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.dashboard');;
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }
}
