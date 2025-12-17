<?php

namespace App\Livewire\Dashboard;

use App\Models\ShippingZone;
use App\Models\Vendor;
use Filament\Widgets\TableWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ShippingConfigurationStatus extends TableWidget
{
    protected static ?string $heading = 'Shipping Configuration Status';
    
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function getTableQuery(): Builder|Relation|null
    {
        return Vendor::query()
            ->withCount(['shippingZones', 'shippingCarrierRates'])
            ->with(['shippingZones' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderByRaw('CASE WHEN shipping_zones_count = 0 THEN 0 ELSE 1 END')
            ->orderBy('store_name');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('store_name')
                ->label('Vendor')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('shipping_zones_count')
                ->label('Zones')
                ->sortable()
                ->formatStateUsing(function ($state, $record) {
                    $activeZones = $record->shippingZones->count();
                    return $activeZones . '/' . $state;
                })
                ->color(function ($state, $record) {
                    $activeZones = $record->shippingZones->count();
                    if ($activeZones == 0) return 'danger';
                    if ($activeZones < $state) return 'warning';
                    return 'success';
                }),

            Tables\Columns\TextColumn::make('shipping_carrier_rates_count')
                ->label('Rates')
                ->sortable()
                ->color(function ($state) {
                    if ($state == 0) return 'danger';
                    if ($state < 3) return 'warning';
                    return 'success';
                }),

            Tables\Columns\TextColumn::make('configuration_status')
                ->label('Status')
                ->getStateUsing(function ($record) {
                    $zonesCount = $record->shipping_zones_count;
                    $ratesCount = $record->shipping_carrier_rates_count;
                    
                    if ($zonesCount == 0) return 'No Zones';
                    if ($ratesCount == 0) return 'No Rates';
                    if ($ratesCount < 2) return 'Limited Options';
                    
                    $activeZones = $record->shippingZones->count();
                    if ($activeZones == 0) return 'No Active Zones';
                    
                    return 'Configured';
                })
                ->badge()
                ->color(function ($state) {
                    return match($state) {
                        'Configured' => 'success',
                        'Limited Options' => 'warning',
                        'No Active Zones' => 'danger',
                        default => 'gray',
                    };
                }),

            Tables\Columns\TextColumn::make('action_needed')
                ->label('Action Needed')
                ->getStateUsing(function ($record) {
                    if ($record->shipping_zones_count == 0) {
                        return 'Add shipping zones';
                    }
                    if ($record->shipping_carrier_rates_count == 0) {
                        return 'Add carrier rates';
                    }
                    $activeZones = $record->shippingZones->count();
                    if ($activeZones == 0) {
                        return 'Activate zones';
                    }
                    return 'None';
                })
                ->wrap(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('configure')
                ->label('Configure')
                ->icon('heroicon-o-cog')
                ->color('primary')
                ->url(fn ($record) => route('filament.admin.resources.shipping-zones.create', [
                    'vendor_id' => $record->id,
                ]))
                ->visible(fn ($record) => $record->shipping_zones_count == 0),

            Tables\Actions\Action::make('add_rates')
                ->label('Add Rates')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->url(function ($record) {
                    if ($record->shippingZones->count() > 0) {
                        return route('filament.admin.resources.shipping-carrier-rates.create', [
                            'vendor_id' => $record->id,
                            'zone_name' => $record->shippingZones->first()->name,
                        ]);
                    }
                    return '#';
                })
                ->visible(fn ($record) => $record->shipping_zones_count > 0 && $record->shipping_carrier_rates_count == 0),

            Tables\Actions\Action::make('view_zones')
                ->label('View Zones')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn ($record) => route('filament.admin.resources.shipping-zones.index', [
                    'tableFilters' => [
                        'vendor' => ['value' => $record->id],
                    ]
                ]))
                ->visible(fn ($record) => $record->shipping_zones_count > 0),
        ];
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-truck';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No vendors found';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Once you add vendors, their shipping configuration status will appear here.';
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label('Add Vendor')
                ->url(route('filament.admin.resources.vendors.create'))
                ->icon('heroicon-o-plus')
                ->button(),
        ];
    }
}