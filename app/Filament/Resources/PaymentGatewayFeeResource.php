<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentGatewayFeeResource\Pages;
use App\Models\PaymentGatewayFee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentGatewayFeeResource extends Resource
{
    protected static ?string $model = PaymentGatewayFee::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Finance Settings';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('gateway_name')
                    ->options([
                        'stripe' => 'Stripe',
                        'om' => 'Orange Money (OM)',
                        'bank_transfer' => 'Bank Transfer',
                    ])
                    ->required()
                    ->unique(ignoreRecord: true),
                    
                Forms\Components\Select::make('fee_type')
                    ->options([
                        'percentage' => 'Percentage Only',
                        'fixed' => 'Fixed Amount Only',
                        'percentage_plus_fixed' => 'Percentage + Fixed',
                    ])
                    ->required()
                    ->live(),
                    
                Forms\Components\TextInput::make('percentage_fee')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->required(fn($get) => in_array($get('fee_type'), ['percentage', 'percentage_plus_fixed']))
                    ->visible(fn($get) => in_array($get('fee_type'), ['percentage', 'percentage_plus_fixed'])),
                    
                Forms\Components\TextInput::make('fixed_fee')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('$')
                    ->required(fn($get) => in_array($get('fee_type'), ['fixed', 'percentage_plus_fixed']))
                    ->visible(fn($get) => in_array($get('fee_type'), ['fixed', 'percentage_plus_fixed'])),
                    
                Forms\Components\TextInput::make('currency')
                    ->default('USD')
                    ->readOnly()
                    ->required()
                    ->maxLength(3),
                    
                Forms\Components\TextInput::make('minimum_fee')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('$')
                    ->nullable(),
                    
                Forms\Components\TextInput::make('maximum_fee')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('$')
                    ->nullable(),
                    
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
                    
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('gateway_name')
                    ->badge()
                    ->color(fn($record) => match($record->gateway_name) {
                        'stripe' => 'success',
                        'orange_money' => 'warning',
                        'paypal' => 'primary',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('fee_type')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('fee_summary')
                    ->label('Fee Structure')
                    ->getStateUsing(function ($record) {
                        if ($record->fee_type === 'percentage') {
                            return $record->percentage_fee . '%';
                        } elseif ($record->fee_type === 'fixed') {
                            return '$' . number_format($record->fixed_fee, 2);
                        } else {
                            return $record->percentage_fee . '% + $' . number_format($record->fixed_fee, 2);
                        }
                    }),
                    
                Tables\Columns\TextColumn::make('currency')
                    ->badge(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gateway_name')
                    ->options([
                        'stripe' => 'Stripe',
                        'orange_money' => 'Orange Money',
                        'paypal' => 'PayPal',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentGatewayFees::route('/'),
            'create' => Pages\CreatePaymentGatewayFee::route('/create'),
            'edit' => Pages\EditPaymentGatewayFee::route('/{record}/edit'),
        ];
    }
}