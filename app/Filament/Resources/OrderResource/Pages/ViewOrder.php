<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            \Filament\Actions\Action::make('invoice_preview')
                ->label('Preview Invoice')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('orders.invoice.preview', $this->record))
                ->openUrlInNewTab(),

            \Filament\Actions\Action::make('invoice_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => route('orders.invoice.pdf', $this->record))
                ->openUrlInNewTab(),
        ];

        // Check if user can edit based on payment status and order status
        if ($this->canUserEditOrder()) {
            $actions[] = Actions\EditAction::make();
        }

        return $actions;
    }

    protected function canUserEditOrder(): bool
    {
        $order = $this->record;
        $user = Auth::user();

        // Check if order is in a restricted state
        $isRestrictedState = in_array($order->payment_status, ['paid']) || 
                            in_array($order->status, ['cancelled', 'delivered']);

        // If order is in restricted state, only managers can edit
        if ($isRestrictedState) {
            // Check if user is a manager (adjust role check based on your user model)
            return $user->hasRole('manager') || 
                   $user->hasRole('admin'); // Adjust based on your permission system
        }

        // If order is not in restricted state, any user with edit permission can edit
        return $user->can('edit', $order); // Using Laravel's authorization
    }
}