<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;
    protected static function isVendorPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'vendor';
    }
    protected function authorizeAccess(): void
    {
        $user = Filament::auth()->user();
        $record = $this->getRecord();
        
        // Admin can access everything
        if (!$this->isVendorPanel()) { // Call static method
            return;
        }
        
        // Vendor can only access if they created it
        if ($record->created_by !== $user->id) {
            Notification::make()
                ->title('Access Denied')
                ->body('You can only edit categories that you created.')
                ->danger()
                ->persistent()
                ->send();
            
            $this->redirect(CategoryResource::getUrl('index'));
        }
    }
    
    protected function getHeaderActions(): array
    {
        //$isVendorPanel = CategoryResource::isVendorPanel(); // Call static method
        
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => !$this->isVendorPanel()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        $data['name_en'] = $record->getTranslation('name', 'en');
        $data['name_fr'] = $record->getTranslation('name', 'fr');
        $data['name_zh'] = $record->getTranslation('name', 'zh');

        $data['family_en'] = $record->getTranslation('family', 'en') ?? $record->family;
        $data['family_fr'] = $record->getTranslation('family', 'fr');
        $data['family_zh'] = $record->getTranslation('family', 'zh');

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['name'] = $data['name_en'] ?? $data['name'] ?? null;
        $data['family'] = $data['family_en'] ?? $data['family'] ?? null;

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        $record->syncTranslations([
            'en' => ['name' => $this->data['name_en'] ?? null, 'family' => $this->data['family_en'] ?? null],
            'fr' => ['name' => $this->data['name_fr'] ?? null, 'family' => $this->data['family_fr'] ?? null],
            'zh' => ['name' => $this->data['name_zh'] ?? null, 'family' => $this->data['family_zh'] ?? null],
        ]);
    }

}