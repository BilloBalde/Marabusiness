<?php

namespace App\Filament\Vendor\Resources\VendorShippingRateResource\Pages;

use App\Filament\Vendor\Resources\VendorShippingRateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorShippingRate extends EditRecord
{
    protected static string $resource = VendorShippingRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
