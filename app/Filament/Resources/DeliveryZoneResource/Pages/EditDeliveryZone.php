<?php

namespace App\Filament\Resources\DeliveryZoneResource\Pages;

use App\Filament\Resources\DeliveryZoneResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryZone extends EditRecord
{
    protected static string $resource = DeliveryZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Deletion stays admin-only: the resource's own canDelete() already blocks
            // it for a vendor, this just keeps the button from appearing at all.
            Actions\DeleteAction::make()->visible(fn () => DeliveryZoneResource::canDelete($this->record)),
        ];
    }

    /**
     * `country_filter` is a virtual field (dehydrated(false)) that only exists to drive
     * `locality_id`'s option list — the record itself has no such column, so Filament's
     * usual default-value fill never reaches it on Edit (that path only applies on
     * Create). Without this, the country selector loads empty, the locality Select's
     * options depend on it and load empty too, and the field falls back to showing the
     * raw stored id instead of its label.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['country_filter'] = DeliveryZoneResource::countryIdFor($this->record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Filament::auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
