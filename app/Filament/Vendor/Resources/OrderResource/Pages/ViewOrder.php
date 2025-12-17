<?php

namespace App\Filament\Vendor\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource\Pages\ViewOrder as BaseViewOrder;
use App\Filament\Vendor\Resources\OrderResource;

class ViewOrder extends BaseViewOrder
{
    protected static string $resource = OrderResource::class;
}
