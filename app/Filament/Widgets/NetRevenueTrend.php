<?php

namespace App\Filament\Widgets;

use App\Models\FinancialTransaction;
use Filament\Widgets\ChartWidget;

class NetRevenueTrend extends ChartWidget
{
    protected static ?string $heading = 'Net Revenue Trend (last 14 days)';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $from = now()->subDays(13)->startOfDay();

        // pick a currency to chart (default first found)
        $currency = FinancialTransaction::query()
            ->whereNotNull('currency')
            ->value('currency') ?? 'USD';

        $rows = FinancialTransaction::query()
            ->selectRaw("DATE(created_at) as day")
            ->selectRaw("SUM(net_amount) as net")
            ->where('status', 'processed')
            ->where('currency', $currency)
            ->where('created_at', '>=', $from)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $data = [];

        for ($i = 0; $i < 14; $i++) {
            $day = $from->copy()->addDays($i)->format('Y-m-d');
            $labels[] = $day;
            $data[] = (float) ($rows[$day]->net ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => "Net ({$currency})",
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
