<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set the creator
        $data['created_by'] = Filament::auth()->id();
        $data['name'] = $data['name_en'] ?? $data['name'] ?? null;
        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        $record->syncTranslations([
            'en' => ['name' => $this->data['name_en'] ?? null],
            'fr' => ['name' => $this->data['name_fr'] ?? null],
            'zh' => ['name' => $this->data['name_zh'] ?? null],
        ]);
    }
}
