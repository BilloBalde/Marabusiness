<?php

namespace App\Filament\Vendor\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class VendorOrderStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $vendorId = auth()->user()?->vendor?->id;

        if (!$vendorId) {
            return [
                Stat::make('New Orders', 0),
                Stat::make('Processing Orders', 0),
                Stat::make('Shipped Orders', 0),
                Stat::make('Average Price', Number::currency(0, config('app.currency', 'USD'))),
            ];
        }

        $query = Order::query()->where('vendor_id', $vendorId);
        $currency = optional(auth()->user()?->vendor?->currency)->code ?? config('app.currency', 'USD');

        // Le panier moyen portait sur TOUTES les commandes, annulées comprises,
        // et maintenant les négociations : une commande en discussion porte un
        // total provisoire que personne n'a accepté, une commande annulée un
        // total jamais encaissé. Ni l'un ni l'autre ne dit ce que vend la
        // boutique. Même exclusion que OrderStats et OrderStatsOverview.
        $settled = (clone $query)->whereNotIn('status', ['cancelled', Order::STATUS_NEGOTIATING]);

        return [
            Stat::make('New Orders', (clone $query)->where('status', 'new')->count()),
            Stat::make('Processing Orders', (clone $query)->where('status', 'processing')->count()),
            Stat::make('Shipped Orders', (clone $query)->where('status', 'shipped')->count()),
            Stat::make(
                'Negotiations',
                (clone $query)->where('status', Order::STATUS_NEGOTIATING)->count()
            )->description('Prix en discussion'),
            Stat::make('Average Price', Number::currency($settled->avg('grand_total') ?? 0, $currency))
                ->description('Hors commandes annulées et en négociation'),
        ];
    }
}
