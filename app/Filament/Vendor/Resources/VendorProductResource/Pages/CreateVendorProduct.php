<?php

namespace App\Filament\Vendor\Resources\VendorProductResource\Pages;

use App\Filament\Vendor\Resources\VendorProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateVendorProduct extends CreateRecord
{
    protected static string $resource = VendorProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        $data['vendor_id'] = $user->vendor_id ?? $user->vendor?->id;

        return $data;
    }
}
