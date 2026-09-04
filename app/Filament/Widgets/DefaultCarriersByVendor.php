<?php

namespace App\Filament\Widgets;

use App\Models\ShippingCarrierRate;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class DefaultCarriersByVendor extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getTableQuery(): Builder
    {
        return ShippingCarrierRate::query()
            ->select('vendor_id', 'zone_name')
            ->selectRaw("SUM(CASE WHEN is_default = 1 THEN 1 ELSE 0 END) as defaults_count")
            ->selectRaw("SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count")
            ->groupBy('vendor_id', 'zone_name')
            ->orderBy('vendor_id')
            ->orderBy('zone_name');
    }

    public function getTableRecordKey($record): string
    {
        return (string) ($record->vendor_id . '-' . $record->zone_name);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('vendor.store_name')
                ->label('Vendor')
                ->getStateUsing(fn ($record) => $record->vendor?->store_name ?? '—')
                ->sortable(),

            Tables\Columns\TextColumn::make('zone_name')
                ->label('Zone')
                ->badge()
                ->sortable(),

            Tables\Columns\TextColumn::make('active_count')
                ->label('Active Rates')
                ->sortable(),

            Tables\Columns\IconColumn::make('defaults_count')
                ->label('Has Default?')
                ->boolean()
                ->getStateUsing(fn ($record) => (int) $record->defaults_count > 0),
        ];
    }

    protected function getTableHeading(): string
    {
        return 'Default Carrier Coverage (Vendor + Zone)';
    }

    protected function getTableDefaultPaginationPageOption(): int
    {
        return 6;
    }
}
