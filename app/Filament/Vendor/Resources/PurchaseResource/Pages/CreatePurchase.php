<?php

namespace App\Filament\Vendor\Resources\PurchaseResource\Pages;

use App\Filament\Vendor\Resources\PurchaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return PurchaseResource::preparePurchaseData($data);
    }
}
