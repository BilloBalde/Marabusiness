<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Resources\OrderResource as BaseOrderResource;
use App\Filament\Vendor\Resources\OrderResource\Pages;
use App\Models\Paiement;
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

    /**
     * Payments a buyer has declared and nobody has confirmed receiving.
     *
     * This badge used to show the vendor's total number of orders — a figure that only
     * ever grows, turns red for good at eleven orders, and asks nothing of anyone. Now
     * that a buyer's declaration waits on the vendor, the useful count is the one that
     * goes back to zero when the work is done.
     */
    protected static function pendingPaymentConfirmations(): int
    {
        $vendorId = auth()->user()?->vendor?->id;

        if (! $vendorId) {
            return 0;
        }

        return Paiement::whereNull('confirmed_at')
            ->whereHas('order', fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->count();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::pendingPaymentConfirmations();

        // Nothing to do, nothing shown: a permanent badge stops being read.
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::pendingPaymentConfirmations() > 0 ? 'warning' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = static::pendingPaymentConfirmations();

        if ($count === 0) {
            return null;
        }

        return $count === 1
            ? 'Un paiement déclaré attend votre confirmation'
            : "{$count} paiements déclarés attendent votre confirmation";
    }
}
