<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

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
            
            $this->redirect(BrandResource::getUrl('index'));
        }
    }
    
    protected function getHeaderActions(): array
    {
        
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

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['name'] = $data['name_en'] ?? $data['name'] ?? null;
        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        $record->syncTranslations([
            'en' => ['name' => $this->data['name_en'] ?? null],
            'fr' => ['name' => $this->data['name_fr'] ?? null],
            'zh' => ['name' => $this->data['name_zh'] ?? null],
        ]);
    }

}
