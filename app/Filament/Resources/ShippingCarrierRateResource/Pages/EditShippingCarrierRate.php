<?php

namespace App\Filament\Resources\ShippingCarrierRateResource\Pages;

use App\Filament\Resources\ShippingCarrierRateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShippingCarrierRate extends EditRecord
{
    protected static string $resource = ShippingCarrierRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
