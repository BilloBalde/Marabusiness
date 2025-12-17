<?php

namespace App\Livewire\Vendor\Dashboard;

use Filament\Widgets\TableWidget;
use Filament\Tables;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class LowStock extends TableWidget
{
    protected function getTableQuery(): Builder|Relation|null
    {
        return Product::where('vendor_id', auth()->user()->vendor->id)
            ->where('stock', '<', 10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('stock')->label('Stock')->badge(),
            Tables\Columns\TextColumn::make('category.name')->label('Category'),
        ];
    }
}
