<?php

namespace App\Filament\Resources\SourcingResource\Pages;

use App\Filament\Resources\SourcingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSourcings extends ListRecords
{
    protected static string $resource = SourcingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
