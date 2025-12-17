<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
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
                Tables\Columns\TextColumn::make('transaction_id'),
                Tables\Columns\TextColumn::make('payment_method'),
                Tables\Columns\TextColumn::make('payment_status'),
                Tables\Columns\TextColumn::make('amount'),
                Tables\Columns\TextColumn::make('currency'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function ($record, $livewire) {
                        $this->updateOrderTotals($livewire->ownerRecord);
                        $livewire->ownerRecord->refresh();   // 👈 VERY IMPORTANT
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record, $livewire) {
                        $this->updateOrderTotals($livewire->ownerRecord);
                        $livewire->ownerRecord->refresh();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->after(function ($record, $livewire) {
                        $this->updateOrderTotals($livewire->ownerRecord);
                        $livewire->ownerRecord->refresh();
                    }),
            ]);
    }

    private function updateOrderTotals($order)
    {
        $totalPaid = $order->paiements()->sum('amount');
        $remaining = max(0, $order->grand_total - $totalPaid);

        $order->update([
            'total_paid'      => $totalPaid,
            'total_remaining' => $remaining,
            'payment_status'  => $remaining <= 0 ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid'),
        ]);
    }
}
