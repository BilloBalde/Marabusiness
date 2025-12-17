<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Resources\PurchaseResource as BasePurchaseResource;
use App\Filament\Vendor\Resources\PurchaseResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class PurchaseResource extends BasePurchaseResource
{
    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Purchases';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function shouldRegisterNavigation(): bool
    {
        // Hide Purchase from the vendor sidebar
        return false;
    }

    public static function canViewAny(): bool
    {
        // Disable access for vendor panel users
        return false;
    }

    protected static function hideVendorField(): bool
    {
        return true;
    }

    protected static function disableVendorField(): bool
    {
        return true;
    }

    protected static function allowProductCreation(): bool
    {
        return true;
    }

    protected static function showVendorColumn(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $vendorId = auth()->user()?->vendor?->id ?? 0;

        return parent::getEloquentQuery()
            ->where('vendor_id', $vendorId);
    }

    public static function getNavigationBadge(): ?string
    {
        $vendorId = auth()->user()?->vendor?->id;

        if (!$vendorId) {
            return null;
        }

        return (string) static::getModel()::query()
            ->where('vendor_id', $vendorId)
            ->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
