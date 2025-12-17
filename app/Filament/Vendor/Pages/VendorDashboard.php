<?php

namespace App\Filament\Vendor\Pages;

use Filament\Pages\Dashboard;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Facades\Filament;

class VendorDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.vendor.dashboard';

    public function getHeading(): string
    {
        return __('filament.nav.dashboard');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.dashboard');;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('vendor');
    }
}
