<?php

namespace App\Filament\Widgets;

use App\Models\ShippingZone;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopVendorsByZones extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getTableQuery(): Builder
    {
        return ShippingZone::query()
            ->select('vendor_id')
            ->selectRaw('COUNT(*) as zones_count')
            ->groupBy('vendor_id')
            ->orderByDesc('zones_count');
    }

    public function getTableRecordKey($record): string
    {
        // aggregated -> must be stable
        return (string) $record->vendor_id;
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('vendor.store_name')
                ->label('Vendor')
                ->getStateUsing(fn ($record) => $record->vendor?->store_name ?? '—')
                ->sortable(),

            Tables\Columns\TextColumn::make('zones_count')
                ->label('Zones')
                ->sortable(),
        ];
    }

    protected function getTableHeading(): string
    {
        return 'Top Vendors by Zones';
    }

    protected function getTableDefaultPaginationPageOption(): int
    {
        return 5;
    }
}
