<?php

namespace App\Filament\Vendor\Resources\BulkRfqResource\Pages;

use App\Filament\Vendor\Resources\BulkRfqResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBulkRfq extends EditRecord
{
    protected static string $resource = BulkRfqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
