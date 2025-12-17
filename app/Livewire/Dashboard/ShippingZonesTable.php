<?php

namespace App\Livewire\Dashboard;

use App\Models\ShippingZone;
use Filament\Widgets\TableWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ShippingZonesTable extends TableWidget
{
    protected static ?string $heading = 'Shipping Zones';
    
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getTableQuery(): Builder|Relation|null
    {
        return ShippingZone::query()
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

            Tables\Columns\TextColumn::make('name')
                ->label('Zone Name')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('display_name')
                ->label('Full Zone')
                ->description(fn ($record) => $record->description)
                ->wrap()
                ->searchable(['name', 'country_code', 'region']),

            Tables\Columns\TextColumn::make('country_code')
                ->label('Country')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('base_price')
                ->label('Base Price')
                ->money('USD')
                ->sortable(),

            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean()
                ->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view')
                ->label('View')
                ->icon('heroicon-o-eye')
                ->url(fn ($record) => route('filament.admin.resources.shipping-zones.edit', $record)),
        ];
    }
}