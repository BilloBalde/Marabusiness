<?php

namespace App\Livewire\Vendor\Dashboard;

use Livewire\Component;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;

class VendorStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $vendorId = auth()->user()->vendor->id;

        $orders = Order::where('vendor_id', $vendorId)
            ->with('vendor.currency')
            ->get();

        $averageUsd = $orders->map(function ($order) {
            $rate = $order->vendor->currency->rate_to_usd ?? 1;
            return $order->grand_total * $rate;
        })->avg();

        return [
            Stat::make('New Orders', $orders->where('status', 'new')->count())
                ->color('primary')
                ->description('Waiting to process'),

            Stat::make('Processing', $orders->where('status', 'processing')->count())
                ->color('warning')
                ->description('Orders in progress'),

            Stat::make('Shipped', $orders->where('status', 'shipped')->count())
                ->color('success')
                ->description('Completed shipments'),

            Stat::make('Avg Order (USD)', '$' . number_format($averageUsd, 2))
                ->color('info')
                ->description('Converted to USD'),
        ];
    }
}
