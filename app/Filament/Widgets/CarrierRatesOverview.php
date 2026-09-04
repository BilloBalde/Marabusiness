<?php

namespace App\Filament\Widgets;

use App\Models\ShippingCarrierRate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CarrierRatesOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $total = ShippingCarrierRate::count();
        $active = ShippingCarrierRate::where('is_active', true)->count();
        $defaults = ShippingCarrierRate::where('is_default', true)->count();

        return [
            Stat::make('Total Rates', (string) $total),
            Stat::make('Active Rates', (string) $active),
            Stat::make('Default Carriers', (string) $defaults),
        ];
    }
}
