<?php

namespace App\Filament\Vendor\Resources\VendorShippingRateResource\Pages;

use App\Filament\Vendor\Resources\VendorShippingRateResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorShippingRate extends CreateRecord
{
    protected static string $resource = VendorShippingRateResource::class;

    /**
     * The vendor is never a form field: it is always the signed-in shop.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['vendor_id'] = Filament::auth()->user()?->vendor?->id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
