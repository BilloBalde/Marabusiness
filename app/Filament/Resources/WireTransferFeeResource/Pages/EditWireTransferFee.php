<?php

namespace App\Filament\Resources\WireTransferFeeResource\Pages;

use App\Filament\Resources\WireTransferFeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWireTransferFee extends EditRecord
{
    protected static string $resource = WireTransferFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
