<?php

namespace App\Livewire\Dashboard;

use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;

class BestProductsChart extends ChartWidget
{
    protected static ?string $heading = 'Best Selling Products';

    protected function getData(): array
    {
        $result = OrderItem::selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->with('product:id,name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Units Sold',
                    'data' => $result->pluck('qty'),
                    'backgroundColor' => 'rgba(59,130,246,0.6)',
                ]
            ],
            'labels' => $result->pluck('product.name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
