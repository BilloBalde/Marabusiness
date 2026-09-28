<?php

namespace App\Filament\Resources\SourcingResource\Pages;

use App\Filament\Resources\SourcingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSourcing extends EditRecord
{
    protected static string $resource = SourcingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
