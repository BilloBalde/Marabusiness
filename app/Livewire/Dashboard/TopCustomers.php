<?php

namespace App\Livewire\Dashboard;

use App\Models\Order;
use App\Models\User;
use Filament\Widgets\TableWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class TopCustomers extends TableWidget
{
    protected static ?string $heading = 'Top Customers';

    protected function getTableQuery(): Builder|Relation|null
    {
        return Order::query()
            ->selectRaw('user_id, SUM(grand_total) as total_spent, COUNT(*) as orders_count')
            ->groupBy('user_id')
            ->orderByDesc('total_spent');
    }

    public function getTableRecordKey(mixed $record): string
    {
        return 'customer-' . $record->user_id;
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('user_id')
                ->label('Customer')
                ->formatStateUsing(function ($state, $record) {
                    return User::find($record->user_id)?->name ?? 'Unknown';
                }),

            Tables\Columns\TextColumn::make('orders_count')
                ->label('Orders'),

            Tables\Columns\TextColumn::make('total_spent')
                ->money('USD')
                ->label('Spent'),
        ];
    }
}
