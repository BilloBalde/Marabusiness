<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Model;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === Purchase::STATUS_DRAFT),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PurchaseResource::preparePurchaseData($data, $this->record);
    }

    protected function afterSave(): void
    {
        /** @var Purchase $purchase */
        $purchase = $this->record;

        $purchase->refreshTotals();
    }
}
