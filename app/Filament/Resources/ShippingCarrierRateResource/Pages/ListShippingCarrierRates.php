<?php

namespace App\Filament\Resources\ShippingCarrierRateResource\Pages;

use App\Filament\Resources\ShippingCarrierRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShippingCarrierRates extends ListRecords
{
    protected static string $resource = ShippingCarrierRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
