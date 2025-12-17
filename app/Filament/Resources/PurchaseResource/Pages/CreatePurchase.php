<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Purchase;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Only normalize vendor / currency here
        return PurchaseResource::preparePurchaseData($data);
    }

    protected function afterCreate(): void
    {
        /** @var Purchase $purchase */
        $purchase = $this->record;

        // Now items are saved, we can recompute totals from purchase_items
        $purchase->refreshTotals();
    }
}
