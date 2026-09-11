<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getHeading(): string
    {
        return __('filament.nav.orders');
    }

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OrderResource\Widgets\OrderStats::class
        ];
    }

    /**
     * The tabs had no entry for 'negotiating', so an order being haggled over was
     * reachable only through "All" — while the vendor's navigation badge counted
     * those very orders and offered nowhere to click. The tab carries its own
     * count, because that is the number the vendor is being asked to act on.
     *
     * Labels are looked up in the translation files rather than left to
     * Filament's auto-labelling of the array keys, which only ever produced
     * English.
     */
    public function getTabs(): array
    {
        return [
            null => Tab::make(__('filament.order_tabs.all')),
            'negotiating' => Tab::make(__('filament.order_tabs.negotiating'))
                ->icon('heroicon-m-chat-bubble-left-right')
                ->query(fn ($query) => $query->where('status', Order::STATUS_NEGOTIATING))
                // Même compte que la pastille de navigation, et une seule
                // définition : Order::scopeAwaitingVendorPrice().
                ->badge(fn () => static::getResource()::getEloquentQuery()
                    ->awaitingVendorPrice()
                    ->count() ?: null)
                ->badgeColor('warning'),
            'new' => Tab::make(__('filament.order_tabs.new'))
                ->query(fn ($query) => $query->where('status', 'new')),
            'processing' => Tab::make(__('filament.order_tabs.processing'))
                ->query(fn ($query) => $query->where('status', 'processing')),
            'shipped' => Tab::make(__('filament.order_tabs.shipped'))
                ->query(fn ($query) => $query->where('status', 'shipped')),
            'delivered' => Tab::make(__('filament.order_tabs.delivered'))
                ->query(fn ($query) => $query->where('status', 'delivered')),
            'cancelled' => Tab::make(__('filament.order_tabs.cancelled'))
                ->query(fn ($query) => $query->where('status', 'cancelled')),
        ];
    }
}
