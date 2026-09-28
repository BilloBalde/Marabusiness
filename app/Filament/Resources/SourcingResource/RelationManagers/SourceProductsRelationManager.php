<?php

namespace App\Filament\Resources\SourcingResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SourceProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'sourceProducts';

    protected static ?string $title = 'Produits';

    public static function getRecordTitleAttribute(): string
    {
        return 'name';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name.fr')
                    ->label('Nom')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('short_description.fr')
                    ->label('Description courte')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('price_range')
                    ->label('Fourchette de prix')
                    ->placeholder('ex. 50 000–80 000 GNF')
                    ->helperText('Texte libre, pas un calcul.'),
                Forms\Components\TextInput::make('quantity_minimum')
                    ->label('Quantité minimum (MOQ)')
                    ->numeric(),
                Forms\Components\TextInput::make('city')
                    ->label('Ville'),
                Forms\Components\FileUpload::make('images')
                    ->label('Images')
                    ->multiple()
                    ->disk('public_uploads')
                    ->directory('sourcing/products')
                    ->visibility('public')
                    ->reorderable()
                    ->panelLayout('grid')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images.0')
                    ->label(''),
                Tables\Columns\TextColumn::make('name.fr')
                    ->label('Nom'),
                Tables\Columns\TextColumn::make('price_range')
                    ->label('Prix'),
                Tables\Columns\TextColumn::make('quantity_minimum')
                    ->label('MOQ'),
                Tables\Columns\TextColumn::make('interest_count')
                    ->label('Sélections'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
