<?php

namespace App\Filament\Vendor\Resources\VendorShippingRateResource\Pages;

use App\Filament\Vendor\Resources\VendorShippingRateResource;
use App\Models\Vendor;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListVendorShippingRates extends ListRecords
{
    protected static string $resource = VendorShippingRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->shippingSettingsAction(),
            Actions\CreateAction::make()->label('Ajouter une localité'),
        ];
    }

    /**
     * Shipping mode and default amount live on the vendor row, not on a rate, but a
     * vendor has no profile screen in this panel — so they are edited here, next to
     * the rates they govern.
     */
    protected function shippingSettingsAction(): Actions\Action
    {
        $vendor = Filament::auth()->user()?->vendor;
        $currency = $vendor?->currency?->code ?? 'USD';

        return Actions\Action::make('shippingSettings')
            ->label('Paramètres de livraison')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->fillForm(fn () => [
                'shipping_mode'           => $vendor?->shipping_mode ?? Vendor::SHIPPING_MODE_CARRIER,
                'default_shipping_amount' => $vendor?->default_shipping_amount,
            ])
            ->form([
                Forms\Components\Radio::make('shipping_mode')
                    ->label('Mode de calcul')
                    ->options(Vendor::SHIPPING_MODES)
                    ->descriptions([
                        Vendor::SHIPPING_MODE_CARRIER  => 'Zones géographiques et grilles transporteurs, configurées par l\'administration.',
                        Vendor::SHIPPING_MODE_LOCALITY => 'Un prix par localité que vous fixez vous-même. Le client ne choisit aucun transporteur.',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('default_shipping_amount')
                    ->label('Montant par défaut')
                    ->helperText('Appliqué quand la localité du client ne figure pas dans vos tarifs. Laissez vide pour ne rien facturer dans ce cas.')
                    ->numeric()
                    ->minValue(0)
                    ->suffix($currency)
                    ->visible(fn (Forms\Get $get) => $get('shipping_mode') === Vendor::SHIPPING_MODE_LOCALITY),
            ])
            ->action(function (array $data) use ($vendor): void {
                if (! $vendor) {
                    return;
                }

                $vendor->update([
                    'shipping_mode'           => $data['shipping_mode'],
                    'default_shipping_amount' => $data['default_shipping_amount'] ?? null,
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
        $vendor = Filament::auth()->user()?->vendor;

        if (! $vendor?->usesLocalityShipping()) {
            return 'Votre boutique est en mode « Zones et transporteurs » : ces tarifs ne sont pas appliqués. '
                 . 'Basculez sur « Prix par localité » dans les paramètres de livraison pour les activer.';
        }

        return $vendor->default_shipping_amount === null
            ? 'Aucun montant par défaut : une commande vers une localité non listée ci-dessous sera livrée sans frais.'
            : null;
    }
}
