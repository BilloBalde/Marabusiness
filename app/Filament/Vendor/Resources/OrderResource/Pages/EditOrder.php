<?php

namespace App\Filament\Vendor\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource\Pages\EditOrder as BaseEditOrder;
use App\Filament\Vendor\Resources\OrderResource;

class EditOrder extends BaseEditOrder
{
    protected static string $resource = OrderResource::class;
}
