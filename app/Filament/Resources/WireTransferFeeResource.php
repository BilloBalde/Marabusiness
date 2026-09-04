<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WireTransferFeeResource\Pages;
use App\Models\WireTransferFee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WireTransferFeeResource extends Resource
{
    protected static ?string $model = WireTransferFee::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Finance Settings';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('country')
                    ->placeholder('Leave empty for global')
                    ->nullable()
                    ->helperText('Leave empty for global fee, or specify country code (e.g., GN, SN, CI)'),
                    
                Forms\Components\TextInput::make('currency')
                    ->default('USD')
                    ->required()
                    ->maxLength(3),
                    
                Forms\Components\Select::make('fee_type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                    ])
                    ->required()
                    ->live(),
                    
                Forms\Components\TextInput::make('percentage_fee')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->required(fn($get) => $get('fee_type') === 'percentage')
                    ->visible(fn($get) => $get('fee_type') === 'percentage'),
                    
                Forms\Components\TextInput::make('fixed_fee')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('$')
                    ->required(fn($get) => $get('fee_type') === 'fixed')
                    ->visible(fn($get) => $get('fee_type') === 'fixed'),
                    
                Forms\Components\TextInput::make('minimum_amount')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('$')
                    ->nullable(),
                    
                Forms\Components\TextInput::make('maximum_amount')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('$')
                    ->nullable(),
                    
                Forms\Components\TextInput::make('processing_days')
                    ->numeric()
                    ->minValue(0)
                    ->default(3)
                    ->suffix('days'),
                    
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('country')
                    ->placeholder('Global')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('currency')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('fee_type')
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('fee_summary')
                    ->label('Fee')
                    ->getStateUsing(function ($record) {
                        if ($record->fee_type === 'percentage') {
                            return $record->percentage_fee . '%';
                        } else {
                            return '$' . number_format($record->fixed_fee, 2);
                        }
                    }),
                    
                Tables\Columns\TextColumn::make('processing_days')
                    ->suffix(' days'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            'index' => Pages\ListWireTransferFees::route('/'),
            'create' => Pages\CreateWireTransferFee::route('/create'),
            'edit' => Pages\EditWireTransferFee::route('/{record}/edit'),
        ];
    }
}