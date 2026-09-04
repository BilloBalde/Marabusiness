<?php

namespace App\Filament\Widgets;

use App\Models\FinancialTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinanceKpiByCurrency extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $from = now()->subDays(30);

        // Sum by currency for processed transactions only
        $rows = FinancialTransaction::query()
            ->select('currency')
            ->selectRaw("SUM(CASE WHEN transaction_type = 'order' THEN amount ELSE 0 END) as gross_orders")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'commission' THEN ABS(amount) ELSE 0 END) as commissions")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'gateway_fee' THEN ABS(amount) ELSE 0 END) as gateway_fees")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'wire_fee' THEN ABS(amount) ELSE 0 END) as wire_fees")
            ->selectRaw("SUM(net_amount) as net_total")
            ->where('status', 'processed')
            ->where('created_at', '>=', $from)
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        if ($rows->isEmpty()) {
            return [
                Stat::make('No data', '—')
                    ->description('No processed transactions in last 30 days'),
            ];
        }

        // Create 1 Stat per currency (super clean)
        return $rows->map(function ($r) {
            $currency = $r->currency ?: '—';

            $label = "30d Net ({$currency})";
            $value = number_format((float) $r->net_total, 2);

            $desc = sprintf(
                "Orders %s | Comm %s | Gateway %s | Wire %s",
                number_format((float) $r->gross_orders, 2),
                number_format((float) $r->commissions, 2),
                number_format((float) $r->gateway_fees, 2),
                number_format((float) $r->wire_fees, 2),
            );

            return Stat::make($label, $value)->description($desc);
        })->toArray();
    }
}
