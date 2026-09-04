<?php

namespace App\Filament\Resources\VendorPayoutResource\Pages;

use App\Filament\Resources\VendorPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorPayout extends EditRecord
{
    protected static string $resource = VendorPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
