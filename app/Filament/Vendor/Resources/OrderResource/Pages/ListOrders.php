<?php

namespace App\Filament\Vendor\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource\Pages\ListOrders as BaseListOrders;
use App\Filament\Vendor\Resources\OrderResource;
use App\Filament\Vendor\Resources\OrderResource\Widgets\VendorOrderStats;

class ListOrders extends BaseListOrders
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            VendorOrderStats::class,
        ];
    }
}
