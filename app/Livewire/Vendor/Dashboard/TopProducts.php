<?php

namespace App\Livewire\Vendor\Dashboard;

use Filament\Widgets\TableWidget;
use Filament\Tables;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class TopProducts extends TableWidget
{
    protected function getTableQuery(): Builder|Relation|null
    {
        return OrderItem::with('product')
            ->whereHas('order', function ($q) {
                $q->where('vendor_id', auth()->user()->vendor->id);
            })
            ->selectRaw('product_id as id, product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('product.name')->label('Product'),
            Tables\Columns\TextColumn::make('qty')->label('Sold')->sortable(),
        ];
    }
}
