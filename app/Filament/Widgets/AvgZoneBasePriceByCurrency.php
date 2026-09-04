<?php

namespace App\Filament\Widgets;

use App\Models\ShippingZone;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class AvgZoneBasePriceByCurrency extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getTableQuery(): Builder
    {
        return ShippingZone::query()
            ->join('vendors', 'vendors.id', '=', 'shipping_zones.vendor_id')
            ->join('currencies', 'currencies.id', '=', 'vendors.currency_id')
            ->selectRaw('currencies.code as currency')
            ->selectRaw('AVG(base_price) as avg_price')
            ->groupBy('currencies.code')
            ->orderBy('currencies.code');
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->currency;
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('currency')
                ->badge(),

            Tables\Columns\TextColumn::make('avg_price')
                ->label('Avg Base Price')
                ->formatStateUsing(fn ($state, $record) =>
                    number_format((float) $state, 2) . ' ' . $record->currency
                ),
        ];
    }
}
