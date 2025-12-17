<?php

namespace App\Livewire\Dashboard;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Sales Last 12 Months';

    protected function getData(): array
    {
        $months = [];
        $totals = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);

            $months[] = $date->format('M');
            $totals[] = Order::whereRaw("strftime('%Y-%m', created_at) = ?", [$date->format('Y-m')])
                ->sum('grand_total');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => $totals,
                    'borderColor' => '#6366F1',
                    'backgroundColor' => 'rgba(99,102,241,0.3)',
                ]
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
