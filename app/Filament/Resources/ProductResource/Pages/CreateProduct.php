<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Filament::auth()->id();
        $data['name'] = $data['name_en'] ?? null;

        if (blank($data['name'])) {
            // This prevents the SQL error and shows a proper validation error
            throw \Illuminate\Validation\ValidationException::withMessages([
                'name_en' => 'Name (EN) is required.',
            ]);
        }

        // Optional: keep EN as base fallback columns
        $data['short_description'] = $data['short_description_en'] ?? $data['short_description'] ?? '';
        $data['description']       = $data['description_en'] ?? $data['description'] ?? '';
        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        $translations = [
            'en' => [
                'name' => $this->data['name_en'] ?? null,
                'short_description' => $this->data['short_description_en'] ?? null,
                'description' => $this->data['description_en'] ?? null,
            ],
        ];

        if (filled($this->data['name_fr'] ?? null)) {
            $translations['fr'] = [
                'name' => $this->data['name_fr'] ?? null,
                'short_description' => $this->data['short_description_fr'] ?? null,
                'description' => $this->data['description_fr'] ?? null,
            ];
        }

        if (filled($this->data['name_zh'] ?? null)) {
            $translations['zh'] = [
                'name' => $this->data['name_zh'] ?? null,
                'short_description' => $this->data['short_description_zh'] ?? null,
                'description' => $this->data['description_zh'] ?? null,
            ];
        }

        $record->syncTranslations($translations);
    }

}
