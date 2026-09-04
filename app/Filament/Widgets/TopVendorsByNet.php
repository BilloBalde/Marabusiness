<?php

namespace App\Filament\Widgets;

use App\Models\FinancialTransaction;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopVendorsByNet extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getTableQuery(): Builder
    {
        return FinancialTransaction::query()
            ->select('vendor_id', 'currency')
            ->selectRaw('SUM(net_amount) as net_sum')
            ->where('status', 'processed')
            ->whereNotNull('vendor_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('vendor_id', 'currency')
            ->orderByDesc('net_sum');
    }

    // 🔑 MUST be public
    public function getTableRecordKey($record): string
    {
        return (string) ($record->vendor_id . '-' . ($record->currency ?? ''));
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('vendor.store_name')
                ->label('Vendor')
                ->getStateUsing(fn ($record) => $record->vendor?->store_name ?? '—')
                ->sortable(),

            Tables\Columns\TextColumn::make('currency')
                ->badge()
                ->sortable(),

            Tables\Columns\TextColumn::make('net_sum')
                ->label('Net (30d)')
                ->sortable()
                ->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
        ];
    }

    protected function getTableHeading(): string
    {
        return 'Top Vendors by Net (30 days)';
    }
}
