<?php

namespace App\Filament\Vendor\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource\Pages\EditOrder as BaseEditOrder;
use App\Filament\Vendor\Resources\OrderResource;
use Illuminate\Support\Arr;

class EditOrder extends BaseEditOrder
{
    protected static string $resource = OrderResource::class;

    /**
     * The fields a vendor may write, whatever the browser sends.
     *
     * The form already disables the rest, but a disabled field is only as strong as
     * the request that respects it. Everything else on the order — customer, shop,
     * lines, shipping cost, payment method and status, totals — is left exactly as the
     * buyer agreed it, and the admin page's item/total rebuild does not run here.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Arr::only($data, ['status', 'shipping_carrier', 'notes']);
    }
}
