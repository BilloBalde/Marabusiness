<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.currencies');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->maxLength(3)
                        ->unique(Currency::class, 'code', ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $operation, $state, Set $set) => $set('code', strtoupper($state))),
                    Forms\Components\TextInput::make('symbol')
                        ->maxLength(8),
                    Forms\Components\TextInput::make('precision')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(6)
                        ->default(2),
                    // This number is a divisor in nine places — RfqOfferConverter,
                    // FinanceCalculator, PaymentController and SuccessPageStripe on
                    // the server, checkout_provider.dart in the mobile app — and only
                    // LocalityShippingCalculator checked it was positive first. Zero
                    // was accepted here and is fatal downstream: PHP 8 throws
                    // DivisionByZeroError, so orders in that currency 500 instead of
                    // taking payment. Blank was worse still, hitting the NOT NULL
                    // column and surfacing a raw QueryException.
                    //
                    // gt:0 rather than a fixed floor: GNF trades at 0.00012, so any
                    // round minimum would lock out the marketplace's main currency.
                    Forms\Components\TextInput::make('rate_to_usd')
                        ->label('Rate to USD')
                        ->helperText('How much 1 unit of this currency is worth in USD. Must be greater than zero.')
                        ->numeric()
                        ->required()
                        ->rule('gt:0')
                        ->default(1),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('symbol')
                    ->label('Symbol'),
                Tables\Columns\TextColumn::make('precision'),
                Tables\Columns\TextColumn::make('rate_to_usd')->numeric(),
                Tables\Columns\BooleanColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
