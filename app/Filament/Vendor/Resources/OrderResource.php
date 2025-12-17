<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Resources\OrderResource as BaseOrderResource;
use App\Filament\Vendor\Resources\OrderResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends BaseOrderResource
{
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    public static function getEloquentQuery(): Builder
    {
        $vendorId = auth()->user()?->vendor?->id;

        return parent::getEloquentQuery()
            ->when($vendorId, fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->when(!$vendorId, fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->vendor()->exists() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
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

    public static function getNavigationBadgeColor(): string|array|null
    {
        $vendorId = auth()->user()?->vendor?->id;

        if (!$vendorId) {
            return null;
        }

        $count = static::getModel()::query()
            ->where('vendor_id', $vendorId)
            ->count();

        return $count > 10 ? 'danger' : 'success';
    }
}
