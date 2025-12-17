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
                    Forms\Components\TextInput::make('rate_to_usd')
                        ->numeric()
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
