<?php

namespace App\Livewire\Dashboard;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $orders = Order::with('vendor.currency')->get();

        // Convert each order total into USD
        $averageUsd = $orders->map(function ($order) {
            $rate = $order->vendor->currency->rate_to_usd ?? 1;
            return $order->grand_total * $rate;
        })->avg();

        return [
            Stat::make('New Orders', $orders->where('status', 'new')->count())
                ->color('primary')
                ->description('Recent orders'),

            Stat::make('Processing Orders', $orders->where('status', 'processing')->count())
                ->color('warning')
                ->description('Currently being processed'),

            Stat::make('Shipped Orders', $orders->where('status', 'shipped')->count())
                ->color('success')
                ->description('Shipped to customers'),

            Stat::make('Average Price', '$' . number_format($averageUsd ?? 0, 2))
                ->color('info')
                ->description('Average order value'),
        ];
    }
}
