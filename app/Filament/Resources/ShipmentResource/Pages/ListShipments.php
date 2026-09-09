<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // shipments:sync exists and works, but nothing runs it on a timer —
            // Render's persistent disk (where the sqlite database actually lives)
            // is bound to this one web service, so a separate Cron Job service
            // could not see the same database file to sync into. Manual trigger,
            // in this same process, sidesteps that entirely.
            Actions\Action::make('syncShipments')
                ->label('Synchroniser le suivi')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Interroge le transporteur pour chaque colis non livré et met à jour son statut.')
                ->action(function () {
                    Artisan::call('shipments:sync');

                    Notification::make()
                        ->title('Synchronisation terminée')
                        ->body(trim(Artisan::output()) ?: 'Aucun colis à synchroniser.')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
