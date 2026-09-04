<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set the creator
        $data['created_by'] = Filament::auth()->id();
        $data['name'] = $data['name_en'] ?? $data['name'] ?? null;
        $data['family'] = $data['family_en'] ?? $data['family'] ?? null;
        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        $record->syncTranslations([
            'en' => ['name' => $this->data['name_en'] ?? null, 'family' => $this->data['family_en'] ?? null],
            'fr' => ['name' => $this->data['name_fr'] ?? null, 'family' => $this->data['family_fr'] ?? null],
            'zh' => ['name' => $this->data['name_zh'] ?? null, 'family' => $this->data['family_zh'] ?? null],
        ]);
    }
}
