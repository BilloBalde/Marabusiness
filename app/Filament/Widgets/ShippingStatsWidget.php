<?php

namespace App\Filament\Widgets;

use App\Models\ShippingZone;
use App\Models\ShippingCarrierRate;
use App\Models\Vendor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ShippingStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s'; // Auto-refresh every 30 seconds
    
    protected function getStats(): array
    {
        $totalZones = ShippingZone::count();
        $activeZones = ShippingZone::where('is_active', true)->count();
        $totalRates = ShippingCarrierRate::count();
        $activeRates = ShippingCarrierRate::where('is_active', true)->count();
        $vendorsWithZones = ShippingZone::distinct('vendor_id')->count();
        $totalVendors = Vendor::count();
        
        // Calculate coverage percentage
        $coveragePercentage = $totalVendors > 0 ? round(($vendorsWithZones / $totalVendors) * 100, 1) : 0;
        
        // Get most popular carrier
        $popularCarrier = ShippingCarrierRate::select('carrier', DB::raw('COUNT(*) as count'))
            ->where('is_active', true)
            ->groupBy('carrier')
            ->orderByDesc('count')
            ->first();
        
        // Get zones by country
        $zonesByCountry = ShippingZone::select('country_code', DB::raw('COUNT(*) as count'))
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(3)
            ->get();
        
        // Calculate inactive percentage
        $inactiveZonesPercentage = $totalZones > 0 ? round((($totalZones - $activeZones) / $totalZones) * 100, 1) : 0;
        $inactiveRatesPercentage = $totalRates > 0 ? round((($totalRates - $activeRates) / $totalRates) * 100, 1) : 0;

        return [
            // Zone Statistics
            Stat::make('Shipping Zones', $totalZones)
                ->description($activeZones . ' active • ' . $inactiveZonesPercentage . '% inactive')
                ->descriptionIcon($activeZones > 0 ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                ->color($activeZones > 0 ? 'success' : 'danger')
                ->chart($this->getZoneActivityChart())
                ->url(route('filament.admin.resources.shipping-zones.index'))
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-gray-50',
                ]),
            
            // Carrier Rate Statistics
            Stat::make('Carrier Rates', $totalRates)
                ->description($activeRates . ' active • ' . $inactiveRatesPercentage . '% inactive')
                ->descriptionIcon($activeRates > 0 ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                ->color($activeRates > 0 ? 'success' : 'warning')
                ->chart($this->getRateActivityChart())
                ->url(route('filament.admin.resources.shipping-carrier-rates.index'))
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-gray-50',
                ]),
            
            // Vendor Coverage
            Stat::make('Vendor Coverage', $vendorsWithZones . '/' . $totalVendors)
                ->description($coveragePercentage . '% coverage • ' . ($totalVendors - $vendorsWithZones) . ' vendors need setup')
                ->descriptionIcon($coveragePercentage >= 80 ? 'heroicon-o-check-badge' : 'heroicon-o-exclamation-triangle')
                ->color($this->getCoverageColor($coveragePercentage))
                ->chart($this->getCoverageChart($vendorsWithZones, $totalVendors))
                ->url(route('filament.admin.resources.vendors.index'))
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-gray-50',
                ]),
            
            // Popular Carrier
            Stat::make('Popular Carrier', $popularCarrier ? ucfirst($popularCarrier->carrier) : 'None')
                ->description($popularCarrier ? $popularCarrier->count . ' zones' : 'No active rates')
                ->descriptionIcon('heroicon-o-star')
                ->color('info')
                ->chart($this->getCarrierDistributionChart())
                ->url($popularCarrier ? route('filament.admin.resources.shipping-carrier-rates.index', [
                    'tableFilters' => [
                        'carrier' => ['value' => $popularCarrier->carrier],
                    ]
                ]) : '#'),
            
            // Zones by Country
            Stat::make('Top Countries', $zonesByCountry->count() . ' countries')
                ->description($this->formatCountriesList($zonesByCountry))
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color('gray')
                ->url(route('filament.admin.resources.shipping-zones.index', [
                    'tableFilters' => [
                        'has_country' => ['value' => true],
                    ]
                ])),
            
            // Default Carriers
            Stat::make('Default Carriers', ShippingCarrierRate::where('is_default', true)->count())
                ->description('Set as default for zones')
                ->descriptionIcon('heroicon-o-flag')
                ->color('purple')
                ->chart($this->getDefaultCarriersChart())
                ->url(route('filament.admin.resources.shipping-carrier-rates.index', [
                    'tableFilters' => [
                        'is_default' => ['value' => true],
                    ]
                ])),
        ];
    }
    
    /**
     * Get color based on coverage percentage
     */
    private function getCoverageColor(float $percentage): string
    {
        if ($percentage >= 90) return 'success';
        if ($percentage >= 70) return 'warning';
        if ($percentage >= 50) return 'orange';
        return 'danger';
    }
    
    /**
     * Format countries list for display
     */
    private function formatCountriesList($zonesByCountry): string
    {
        if ($zonesByCountry->isEmpty()) {
            return 'No countries configured';
        }
        
        $list = [];
        foreach ($zonesByCountry as $zone) {
            $list[] = $zone->country_code . ' (' . $zone->count . ')';
        }
        
        return implode(', ', $zonesByCountry->pluck('country_code')->toArray());
    }
    
    /**
     * Generate chart data for zone activity
     */
    private function getZoneActivityChart(): array
    {
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $active = ShippingZone::whereDate('created_at', '<=', $date)
                ->where('is_active', true)
                ->count();
            $total = ShippingZone::whereDate('created_at', '<=', $date)->count();
            
            $last7Days[] = $total > 0 ? round(($active / $total) * 100) : 0;
        }
        
        return $last7Days;
    }
    
    /**
     * Generate chart data for rate activity
     */
    private function getRateActivityChart(): array
    {
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $active = ShippingCarrierRate::whereDate('created_at', '<=', $date)
                ->where('is_active', true)
                ->count();
            $total = ShippingCarrierRate::whereDate('created_at', '<=', $date)->count();
            
            $last7Days[] = $total > 0 ? round(($active / $total) * 100) : 0;
        }
        
        return $last7Days;
    }
    
    /**
     * Generate chart data for vendor coverage
     */
    private function getCoverageChart(int $withZones, int $totalVendors): array
    {
        $withoutZones = $totalVendors - $withZones;
        return [$withZones, $withoutZones];
    }
    
    /**
     * Generate chart data for carrier distribution
     */
    private function getCarrierDistributionChart(): array
    {
        $carriers = ShippingCarrierRate::select('carrier', DB::raw('COUNT(*) as count'))
            ->where('is_active', true)
            ->groupBy('carrier')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count')
            ->toArray();
        
        // Pad array if less than 5 items
        while (count($carriers) < 5) {
            $carriers[] = 0;
        }
        
        return $carriers;
    }
    
    /**
     * Generate chart data for default carriers
     */
    private function getDefaultCarriersChart(): array
    {
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = ShippingCarrierRate::whereDate('created_at', '<=', $date)
                ->where('is_default', true)
                ->count();
            $last7Days[] = $count;
        }
        
        return $last7Days;
    }
    
    /**
     * Get extra footer content
     */
    protected function getFooter(): ?string
    {
        $needsAttention = $this->getNeedsAttentionCount();
        
        if ($needsAttention > 0) {
            return "
                <div class='p-4 bg-yellow-50 border border-yellow-200 rounded-lg'>
                    <div class='flex items-center'>
                        <svg class='w-5 h-5 text-yellow-400 mr-2' fill='currentColor' viewBox='0 0 20 20'>
                            <path fill-rule='evenodd' d='M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z' clip-rule='evenodd'></path>
                        </svg>
                        <span class='font-medium text-yellow-800'>
                            {$needsAttention} shipping configuration(s) need attention
                        </span>
                    </div>
                </div>
            ";
        }
        
        return null;
    }
    
    /**
     * Count configurations needing attention
     */
    private function getNeedsAttentionCount(): int
    {
        $count = 0;
        
        // Vendors without zones
        $vendorsWithoutZones = Vendor::count() - ShippingZone::distinct('vendor_id')->count();
        $count += $vendorsWithoutZones;
        
        // Active zones without active rates
        $zonesWithoutRates = ShippingZone::where('is_active', true)
            ->whereDoesntHave('carrierRates', function ($query) {
                $query->where('is_active', true);
            })
            ->count();
        $count += $zonesWithoutRates;
        
        // Zones without country/region specification
        $zonesWithoutLocation = ShippingZone::where('is_active', true)
            ->whereNull('country_code')
            ->whereNull('region')
            ->whereJsonLength('cities', 0)
            ->whereNull('radius_km')
            ->count();
        $count += $zonesWithoutLocation;
        
        return $count;
    }
}