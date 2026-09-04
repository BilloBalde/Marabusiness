<?php
// app/Filament/Vendor/Resources/VendorReviewResource/Pages/ListVendorReviews.php

namespace App\Filament\Vendor\Resources\VendorReviewResource\Pages;

use App\Filament\Vendor\Resources\VendorReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorReviews extends ListRecords
{
    protected static string $resource = VendorReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Vendors cannot create reviews, only approve/reject
        ];
    }

    public function getTitle(): string
    {
        return 'Customer Reviews';
    }
}