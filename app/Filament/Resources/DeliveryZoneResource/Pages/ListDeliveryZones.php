<?php

namespace App\Filament\Resources\DeliveryZoneResource\Pages;

use App\Filament\Resources\DeliveryZoneResource;
use App\Models\Vendor;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryZones extends ListRecords
{
    protected static string $resource = DeliveryZoneResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        // A vendor's shipping_mode / default_shipping_amount are settings of that
        // vendor alone (unrelated to the shared price catalogue below), so this action
        // only makes sense in the vendor panel, against the signed-in vendor's own row.
        if (! DeliveryZoneResource::isAdmin()) {
            $actions[] = $this->shippingSettingsAction();
        }

        $actions[] = Actions\CreateAction::make()->label('Ajouter une zone');

        return $actions;
    }

    protected function shippingSettingsAction(): Actions\Action
    {
        $vendor = Filament::auth()->user()?->vendor;
        $currency = $vendor?->currency?->code ?? 'USD';

        return Actions\Action::make('shippingSettings')
            ->label('Paramètres de livraison')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->fillForm(fn () => [
                'shipping_mode' => $vendor?->shipping_mode ?? Vendor::SHIPPING_MODE_CARRIER,
                'default_shipping_amount' => $vendor?->default_shipping_amount,
                'international_shipping_surcharge' => $vendor?->international_shipping_surcharge,
            ])
            ->form([
                Forms\Components\Radio::make('shipping_mode')
                    ->label('Mode de calcul')
                    ->options(Vendor::SHIPPING_MODES)
                    ->descriptions([
                        Vendor::SHIPPING_MODE_CARRIER  => 'Zones géographiques et grilles transporteurs, configurées par l\'administration.',
                        Vendor::SHIPPING_MODE_LOCALITY => 'Le prix partagé de chaque zone, visible et modifiable par tous les vendeurs.',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('default_shipping_amount')
                    ->label('Montant par défaut')
                    ->helperText('Appliqué quand la zone du client n\'a encore de prix fixé par personne. Laissez vide pour ne rien facturer dans ce cas.')
                    ->numeric()
                    ->minValue(0)
                    ->suffix($currency)
                    ->visible(fn (Forms\Get $get) => $get('shipping_mode') === Vendor::SHIPPING_MODE_LOCALITY),

                Forms\Components\TextInput::make('international_shipping_surcharge')
                    ->label('Supplément fret international')
                    ->helperText('Votre boutique n\'est pas en Guinée : ce montant s\'ajoute à chaque commande, en plus du prix de la zone, pour couvrir le transport jusqu\'en Guinée. Laissez à 0 si vous expédiez déjà depuis la Guinée.')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->suffix($currency)
                    ->visible(fn (Forms\Get $get) => $get('shipping_mode') === Vendor::SHIPPING_MODE_LOCALITY),
            ])
            ->action(function (array $data) use ($vendor): void {
                if (! $vendor) {
                    return;
                }

                $vendor->update([
                    'shipping_mode' => $data['shipping_mode'],
                    'default_shipping_amount' => $data['default_shipping_amount'] ?? null,
                    'international_shipping_surcharge' => $data['international_shipping_surcharge'] ?? 0,
                ]);

                Notification::make()
                    ->title('Paramètres de livraison enregistrés')
                    ->success()
                    ->send();
            })
            ->modalSubmitActionLabel('Enregistrer');
    }

    public function getSubheading(): ?string
    {
        if (DeliveryZoneResource::isAdmin()) {
            return 'Prix partagé : une zone tarifée ici s\'applique à tous les vendeurs en mode « Prix par localité », quelle que soit la boutique qui l\'a renseignée.';
        }

        $vendor = Filament::auth()->user()?->vendor;

        if (! $vendor?->usesLocalityShipping()) {
            return 'Votre boutique est en mode « Zones et transporteurs » : ces prix ne sont pas appliqués. Basculez sur « Prix par localité » dans les paramètres de livraison pour les activer.';
        }

        return $vendor->default_shipping_amount === null
            ? 'Aucun montant par défaut : une commande vers une zone non tarifée ci-dessous sera livrée sans frais.'
            : null;
    }
}
