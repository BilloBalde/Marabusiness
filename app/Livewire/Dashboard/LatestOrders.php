<?php

namespace App\Livewire\Dashboard;

use App\Models\Order;
use Filament\Widgets\TableWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class LatestOrders extends TableWidget
{
    protected static ?string $heading = 'Latest Orders';

    public function getTableQuery(): Builder|Relation|null
    {
        return Order::query()
            ->latest()
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('order_number')
                ->label('Order')
                ->searchable(),

            Tables\Columns\TextColumn::make('user.name')
                ->label('Customer'),

            Tables\Columns\TextColumn::make('grand_total')
                ->money('USD')
                ->label('Total'),

            Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'primary' => 'new',
                    'warning' => 'processing',
                    'success' => 'shipped',
                ])
                ->label('Status'),

            Tables\Columns\BadgeColumn::make('payment_status')
                ->colors([
                    'danger' => 'unpaid',
                    'warning' => 'partial',
                    'success' => 'paid',
                ])
                ->label('Payment'),
        ];
    }
}
