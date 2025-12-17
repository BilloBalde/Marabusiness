<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;

class OrderStats extends BaseWidget
{
    protected function getStats(): array
    {
        $stats = DB::table('orders as o')
            ->join('vendors as v', 'o.vendor_id', '=', 'v.id')
            ->join('currencies as c', 'v.currency_id', '=', 'c.id')
            ->where('o.status', '!=', 'cancelled')
            ->select(
                DB::raw('COUNT(o.id) as total_orders'),
                DB::raw('SUM(o.grand_total * c.rate_to_usd) as total_revenue_usd'),
                DB::raw('SUM(o.total_paid * c.rate_to_usd) as total_paid_usd'),
                DB::raw('AVG(o.grand_total * c.rate_to_usd) as avg_order_usd')
            )
            ->first();
        
        $totalRevenue = $stats->total_revenue_usd ?? 0;
        $totalPaid = $stats->total_paid_usd ?? 0;
        $paymentRate = $totalRevenue > 0 ? ($totalPaid / $totalRevenue) * 100 : 0;
        
        return [
            Stat::make('Total Orders', $stats->total_orders ?? 0)
                ->description('All non-cancelled orders')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('primary'),
            
            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('Converted to USD')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
            
            Stat::make('Avg Order Value', '$' . number_format($stats->avg_order_usd ?? 0, 2))
                ->description('In USD')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('info'),
            
            Stat::make('Payment Rate', number_format($paymentRate, 1) . '%')
                ->description('Paid / Total Revenue')
                ->descriptionIcon('heroicon-o-credit-card')
                ->color($paymentRate >= 80 ? 'success' : ($paymentRate >= 50 ? 'warning' : 'danger')),
        ];
    }
}
