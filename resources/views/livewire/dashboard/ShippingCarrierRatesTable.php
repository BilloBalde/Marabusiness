<?php

namespace App\Livewire\Dashboard;

use App\Models\ShippingCarrierRate;
use Filament\Widgets\TableWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ShippingCarrierRatesTable extends TableWidget
{
    protected static ?string $heading = 'Carrier Rates';
    
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getTableQuery(): Builder|Relation|null
    {
        return ShippingCarrierRate::query()
            ->with('vendor')
            ->latest()
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('vendor.store_name')
                ->label('Vendor')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('zone_name')
                ->label('Zone')
                ->searchable()
                ->sortable()
                ->badge()
                ->color('gray'),

            Tables\Columns\TextColumn::make('carrier_name')
                ->label('Carrier')
                ->formatStateUsing(fn ($record) => $record->carrier_name)
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('service_code')
                ->label('Service')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('base_rate')
                ->label('Base Rate')
                ->money('USD')
                ->sortable(),

            Tables\Columns\TextColumn::make('delivery_days')
                ->label('Days')
                ->sortable()
                ->formatStateUsing(fn ($state) => $state . ' days'),

            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean()
                ->sortable(),

            Tables\Columns\IconColumn::make('is_default')
                ->label('Default')
                ->boolean()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view')
                ->label('View')
                ->icon('heroicon-o-eye')
                ->url(fn ($record) => route('filament.admin.resources.shipping-carrier-rates.edit', $record)),
        ];
    }
}