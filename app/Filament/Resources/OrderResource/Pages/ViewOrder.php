<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
            Actions\EditAction::make(),
        ];
    }
}
