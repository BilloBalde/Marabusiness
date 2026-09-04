<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorPayoutResource\Pages;
use App\Models\VendorPayout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorPayoutResource extends Resource
{
    protected static ?string $model = VendorPayout::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vendor_id')
                    ->relationship('vendor', 'store_name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn($operation) => $operation === 'edit'),
                    
                Forms\Components\Select::make('payout_method')
                    ->options([
                        'bank_wire' => 'Bank Wire Transfer',
                        'orange_money' => 'Orange Money',
                        'stripe_transfer' => 'Stripe Transfer',
                        'other' => 'Other',
                    ])
                    ->required(),
                    
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->disabled(fn($operation) => $operation === 'edit'),
                    
                Forms\Components\TextInput::make('commission_amount')
                    ->numeric()
                    ->required()
                    ->disabled(fn($operation) => $operation === 'edit'),
                    
                Forms\Components\TextInput::make('gateway_fees')
                    ->numeric()
                    ->required()
                    ->disabled(fn($operation) => $operation === 'edit'),
                    
                Forms\Components\TextInput::make('wire_fees')
                    ->numeric()
                    ->required()
                    ->disabled(fn($operation) => $operation === 'edit'),
                    
                Forms\Components\TextInput::make('net_amount')
                    ->numeric()
                    ->required()
                    ->disabled(fn($operation) => $operation === 'edit'),
                    
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                    
                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.store_name')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('reference_number')
                    ->copyable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('payout_method')
                    ->badge()
                    ->formatStateUsing(fn($state) => str($state)->replace('_', ' ')->title()),
                    
                Tables\Columns\TextColumn::make('net_amount')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'pending' => 'info',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('vendor')
                    ->relationship('vendor', 'store_name')
                    ->multiple()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
                    
                Tables\Filters\SelectFilter::make('payout_method')
                    ->options([
                        'bank_wire' => 'Bank Wire',
                        'orange_money' => 'Orange Money',
                        'stripe_transfer' => 'Stripe Transfer',
                    ]),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when($data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_completed')
                    ->action(fn($record) => $record->update(['status' => 'completed', 'processed_at' => now()]))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn($record) => $record->status !== 'completed'),
                    
                Tables\Actions\Action::make('view_details')
                    ->modalContent(fn($record) => view('filament.resources.vendor-payout-resource.details-modal', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorPayouts::route('/'),
            'create' => Pages\CreateVendorPayout::route('/create'),
            'edit' => Pages\EditVendorPayout::route('/{record}/edit'),
        ];
    }
}