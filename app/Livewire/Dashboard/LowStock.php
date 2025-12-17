<?php

namespace App\Livewire\Dashboard;

use App\Models\VendorProduct;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStock extends TableWidget
{
    protected static ?string $heading = 'Low Stock Products';

    protected function getTableQuery(): Builder
    {
        return VendorProduct::query()
            ->with(['product', 'product.category'])
            ->where('stock', '<=', 5)
            ->orderBy('stock', 'asc');
    }

    public function getTableRecordKey(mixed $record): string
    {
        return (string) $record->id;
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('product.name')->label('Product')->searchable(),
            Tables\Columns\TextColumn::make('stock')->label('Stock')->sortable(),
            Tables\Columns\TextColumn::make('product.category.name')->label('Category')->sortable(),
        ];
    }
}
