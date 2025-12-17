<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Resources\ShipmentResource as BaseShipmentResource;
use App\Filament\Vendor\Resources\ShipmentResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class ShipmentResource extends BaseShipmentResource
{
    protected static ?string $navigationGroup = 'Logistics';
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function getEloquentQuery(): Builder
    {
        $vendorId = auth()->user()?->vendor?->id;

        return parent::getEloquentQuery()
            ->when(
                $vendorId,
                fn (Builder $query) => $query->whereHas(
                    'order',
                    fn (Builder $orderQuery) => $orderQuery->where('vendor_id', $vendorId)
                )
            )
            ->when(!$vendorId, fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->vendor()->exists() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->vendor()->exists() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $vendorId = auth()->user()?->vendor?->id;

        if (!$vendorId) {
            return null;
        }

        return (string) static::getModel()::query()
            ->whereHas('order', fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $vendorId = auth()->user()?->vendor?->id;

        if (!$vendorId) {
            return null;
        }

        $count = static::getModel()::query()
            ->whereHas('order', fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->count();

        return $count > 5 ? 'warning' : 'success';
    }
}

