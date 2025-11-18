<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Closure;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaiementsRelationManager extends RelationManager
{
    protected static string $relationship = 'paiements';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('transaction_id')
                    ->default(function () {
                        return \App\Models\Order::generateTransactionNumber(); // call the method
                    })
                    ->disabled() // make it non-editable
                    ->dehydrated() // ensure it's saved to the database
                    ->required()
                    ->maxLength(255),

                TextInput::make('amount')->numeric()->required(),
                Select::make('payment_method')
                    ->options([
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'cod' => 'Cash on Delivery',
                        'om' => 'Orange Money',
                    ])
                    ->required(),
                Select::make('currency')
                    ->options([
                        'usd' => 'USD',
                        'cad' => 'CAD',
                        'gnf' => 'GNF',
                    ])
                    ->required(),
                FileUpload::make('image')
                    ->disk('public_uploads')
                    ->directory('payments')
                    ->label('Payment Screenshot')
                    ->previewable(true),
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
            ->recordTitleAttribute('transaction_id')
            ->columns([
                TextColumn::make('amount'),
                TextColumn::make('currency'),
                TextColumn::make('payment_method'),
                TextColumn::make('payment_status'),
                ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public_uploads')
                    ->circular(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function ($record) {
                        $order = $record->order;
                        $totalPaid = $order->paiements()->sum('amount');
                        $remaining = max(0, $order->grand_total - $totalPaid);

                        $order->update([
                            'total_paid' => $totalPaid,
                            'total_remaining' => $remaining,
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        $order = $record->order;
                        $totalPaid = $order->paiements()->sum('amount');
                        $remaining = max(0, $order->grand_total - $totalPaid);

                        $order->update([
                            'total_paid' => $totalPaid,
                            'total_remaining' => $remaining,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        $order = $record->order;
                        $totalPaid = $order->paiements()->sum('amount');
                        $remaining = max(0, $order->grand_total - $totalPaid);

                        $order->update([
                            'total_paid' => $totalPaid,
                            'total_remaining' => $remaining,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->after(function (Collection $records) {
                        foreach ($records as $record) {
                            $order = $record->order;
                            $totalPaid = $order->paiements()->sum('amount');
                            $remaining = max(0, $order->grand_total - $totalPaid);

                            $order->update([
                                'total_paid' => $totalPaid,
                                'total_remaining' => $remaining,
                            ]);
                        }
                    }),
            ]);
    }

}
