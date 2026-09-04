<?php
// app/Filament/Vendor/Resources/VendorReviewResource/Pages/EditVendorReview.php

namespace App\Filament\Vendor\Resources\VendorReviewResource\Pages;

use App\Filament\Vendor\Resources\VendorReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorReview extends EditRecord
{
    protected static string $resource = VendorReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure only is_approved can be changed
        return [
            'is_approved' => $data['is_approved'] ?? false,
        ];
    }
}