<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;

class PaiementsRelationManager extends RelationManager
{
    protected static string $relationship = 'paiements';

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('payment_method')
                ->required()
                ->options([
                    'cash' => 'Cash',
                    'stripe' => 'Stripe',
                    'paypal' => 'PayPal',
                    'cod' => 'Cash on Delivery',
                    'om' => 'Orange Money',
                ]),

            TextInput::make('amount')
                ->numeric()
                ->minValue(0.01)
                ->required()
                ->prefix(fn($livewire) => optional($livewire->ownerRecord->vendor->currency)->code ?? 'USD'),

            FileUpload::make('image')
                ->label('Receipt')
                ->image()
                ->directory('payments')
                ->visibility('public')
                ->nullable(),

            Select::make('payment_status')
                ->options([
                    'pending' => 'Pending',
                    'partial' => 'Partial',
                    'paid' => 'Paid',
                ])
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')->label('Référence'),
                Tables\Columns\TextColumn::make('payment_method')->label('Moyen'),

                // What the vendor actually needs to see: has this money arrived, or is
                // it only something the buyer has told us?
                Tables\Columns\TextColumn::make('confirmed_at')
                    ->label('Réception')
                    ->badge()
                    ->color(fn ($record) => $record->confirmed_at ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state
                        ? 'Encaissé le ' . $state->format('d/m/Y H:i')
                        : 'Déclaré — à confirmer'),

                Tables\Columns\ImageColumn::make('image')
                    ->label('Justificatif')
                    ->disk('public')
                    ->height(40)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('amount')->label('Montant'),
                Tables\Columns\TextColumn::make('currency')->label('Devise'),
                Tables\Columns\TextColumn::make('created_at')->label('Déclaré le')->dateTime(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    // Recorded from the back office by someone who has the money in
                    // hand, so it counts immediately — unlike a buyer's declaration.
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['confirmed_at'] = now();
                        $data['confirmed_by'] = auth()->id();

                        return $data;
                    })
                    ->after(function ($record, $livewire) {
                        $this->updateOrderTotals($livewire->ownerRecord);
                        $this->refreshForm($livewire);
                    }),
            ])
            ->actions([
                // The vendor's side of the distinction the schema now makes: a buyer
                // declaring a cash-on-delivery payment no longer settles their own
                // order — someone who handled the money says so here.
                Tables\Actions\Action::make('confirmerReception')
                    ->label('Confirmer la réception')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->isConfirmed())
                    ->requiresConfirmation()
                    ->modalHeading('Confirmer la réception du paiement')
                    ->modalDescription(fn ($record) => "Confirmez avoir reçu {$record->amount} {$record->currency} "
                        . "({$record->payment_method}). Le solde de la commande sera mis à jour.")
                    ->modalSubmitActionLabel('Oui, j\'ai reçu cet argent')
                    ->action(function ($record, $livewire) {
                        $record->confirm(auth()->user());
                        $this->refreshForm($livewire);

                        Notification::make()
                            ->title('Paiement confirmé')
                            ->body('Le solde de la commande a été mis à jour.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->after(function ($record, $livewire) {
                        $this->updateOrderTotals($livewire->ownerRecord);
                        $this->refreshForm($livewire);
                    }),

                Tables\Actions\DeleteAction::make()
                    ->after(function ($record, $livewire) {
                        $this->updateOrderTotals($livewire->ownerRecord);
                        $this->refreshForm($livewire);
                    }),
            ]);
    }

    /**
     * Was its own copy of the balance rules, summing every payment including ones the
     * shop has not received. Order::syncPaymentTotals() owns that logic now and counts
     * confirmed money only.
     */
    private function updateOrderTotals($order)
    {
        $order->syncPaymentTotals();
    }

    private function refreshForm($livewire)
    {
        // Refresh the owner record to get updated payment totals
        $livewire->ownerRecord->refresh();
        
        // This will trigger the form to recalculate
        if (method_exists($livewire, 'dispatchFormEvent')) {
            $livewire->dispatchFormEvent('refresh');
        }
        
        // Also send a browser event to refresh the form
        $this->dispatch('refresh');
    }
}