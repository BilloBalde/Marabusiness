<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinancialTransactionResource\Pages;
use App\Models\FinancialTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinancialTransactionResource extends Resource
{
    protected static ?string $model = FinancialTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'order_number')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                    
                Forms\Components\Select::make('vendor_id')
                    ->relationship('vendor', 'store_name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                    
                Forms\Components\Select::make('transaction_type')
                    ->options([
                        'order' => 'Order Revenue',
                        'commission' => 'Commission',
                        'gateway_fee' => 'Gateway Fee',
                        'wire_fee' => 'Wire Fee',
                        'payout' => 'Payout',
                        'refund' => 'Refund',
                        'adjustment' => 'Adjustment',
                    ])
                    ->required(),
                    
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required(),
                    
                Forms\Components\TextInput::make('gateway_fee')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('commission_fee')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('wire_fee')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('net_amount')
                    ->numeric()
                    ->required(),
                    
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processed' => 'Processed',
                        'failed' => 'Failed',
                        'reversed' => 'Reversed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                    
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('vendor.store_name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('transaction_type')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'order' => 'success',
                        'commission' => 'warning',
                        'payout' => 'primary',
                        'refund' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('net_amount')
                    ->sortable()
                    ->color(fn($record) => $record->net_amount < 0 ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('currency')
                    ->sortable(),    

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'processed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options([
                        'order' => 'Order Revenue',
                        'commission' => 'Commission',
                        'gateway_fee' => 'Gateway Fee',
                        'payout' => 'Payout',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processed' => 'Processed',
                        'failed' => 'Failed',
                    ]),
                    
                Tables\Filters\SelectFilter::make('vendor')
                    ->relationship('vendor', 'store_name')
                    ->multiple()
                    ->preload(),
                    
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date'),
                        Forms\Components\DatePicker::make('to_date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when($data['to_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListFinancialTransactions::route('/'),
            'create' => Pages\CreateFinancialTransaction::route('/create'),
            'edit' => Pages\EditFinancialTransaction::route('/{record}/edit'),
        ];
    }
}