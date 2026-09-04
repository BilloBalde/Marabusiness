<?php

namespace App\Filament\Widgets;

use App\Models\ShippingZone;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShippingZonesOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $total = ShippingZone::count();
        $active = ShippingZone::where('is_active', true)->count();
        $withCountry = ShippingZone::whereNotNull('country_code')->count();

        return [
            Stat::make('Total Zones', (string) $total),
            Stat::make('Active Zones', (string) $active),
            Stat::make('Zones With Country', (string) $withCountry),
        ];
    }
}
