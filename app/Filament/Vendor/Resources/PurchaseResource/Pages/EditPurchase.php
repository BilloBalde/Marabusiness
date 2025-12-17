<?php

namespace App\Filament\Vendor\Resources\PurchaseResource\Pages;

use App\Filament\Vendor\Resources\PurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === $this->record::STATUS_DRAFT),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PurchaseResource::preparePurchaseData($data);
    }
}
