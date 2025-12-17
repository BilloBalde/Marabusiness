<?php

namespace App\Livewire\Dashboard;

use App\Models\Paiement;
use Filament\Widgets\ChartWidget;

class PaymentMethodChart extends ChartWidget
{
    protected static ?string $heading = 'Payment Methods';

    protected function getData(): array
    {
        $methods = Paiement::selectRaw('payment_method, COUNT(*) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Payments',
                    'data' => array_values($methods),
                    'backgroundColor' => ['#6366F1', '#10B981', '#F59E0B', '#EF4444'],
                ]
            ],
            'labels' => array_keys($methods),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
