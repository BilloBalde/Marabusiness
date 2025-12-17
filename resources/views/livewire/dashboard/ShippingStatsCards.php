<?php

namespace App\Livewire\Dashboard;

use App\Models\ShippingZone;
use App\Models\ShippingCarrierRate;
use App\Models\Vendor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShippingStatsCards extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalZones = ShippingZone::count();
        $activeZones = ShippingZone::where('is_active', true)->count();
        $totalRates = ShippingCarrierRate::count();
        $activeRates = ShippingCarrierRate::where('is_active', true)->count();
        $vendorsWithZones = ShippingZone::distinct('vendor_id')->count();
        $totalVendors = Vendor::count();

        return [
            Stat::make('Shipping Zones', $totalZones)
                ->description($activeZones . ' active')
                ->descriptionIcon($activeZones > 0 ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                ->color($activeZones > 0 ? 'success' : 'danger')
                ->url(route('filament.admin.resources.shipping-zones.index')),

            Stat::make('Carrier Rates', $totalRates)
                ->description($activeRates . ' active')
                ->descriptionIcon($activeRates > 0 ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                ->color($activeRates > 0 ? 'success' : 'warning')
                ->url(route('filament.admin.resources.shipping-carrier-rates.index')),

            Stat::make('Vendor Coverage', $vendorsWithZones . '/' . $totalVendors)
                ->description(round(($vendorsWithZones / max($totalVendors, 1)) * 100, 1) . '% coverage')
                ->descriptionIcon($vendorsWithZones > 0 ? 'heroicon-o-building-storefront' : 'heroicon-o-exclamation-triangle')
                ->color($vendorsWithZones > 0 ? 'primary' : 'gray')
                ->url(route('filament.admin.resources.vendors.index')),
        ];
    }
}