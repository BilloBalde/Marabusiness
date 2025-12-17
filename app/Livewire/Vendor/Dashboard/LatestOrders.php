<?php

namespace App\Livewire\Vendor\Dashboard;

use Filament\Widgets\TableWidget;
use Filament\Tables;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class LatestOrders extends TableWidget
{
    protected function getTableQuery(): Builder|Relation|null
    {
        //dd(auth()->user()->vendor->id);
        return Order::where('vendor_id', auth()->user()->vendor->id)
            ->latest()
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')->label('Order #'),
            Tables\Columns\TextColumn::make('user.name')->label('Customer'),
            Tables\Columns\TextColumn::make('grand_total')
                ->label('Total')
                ->formatStateUsing(fn ($state, $record) =>
                    ($record->vendor->currency->symbol ?? '$') . ' ' . number_format($state, 2)
                ),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ];
    }
}
