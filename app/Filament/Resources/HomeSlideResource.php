<?php

namespace App\Filament\Resources;

use App\Models\HomeSlide;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HomeSlideResource extends Resource
{
    protected static ?string $model = HomeSlide::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Contenu du site';

    protected static ?string $modelLabel = 'Slide (accueil)';

    protected static ?string $pluralModelLabel = 'Slides (accueil)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->disk('public_uploads')
                    ->directory('home-slides')
                    ->required(),
                Forms\Components\TextInput::make('title.fr')
                    ->label('Titre (français)')
                    ->required(),
                Forms\Components\TextInput::make('subtitle.fr')
                    ->label('Sous-titre (français)'),
                Forms\Components\TextInput::make('cta_label.fr')
                    ->label('Texte du bouton (français)')
                    ->placeholder('En savoir plus'),
                Forms\Components\TextInput::make('cta_url')
                    ->label('Lien du bouton')
                    ->placeholder('/sourcing ou /about-us'),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Ordre d\'affichage')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public_uploads'),
                Tables\Columns\TextColumn::make('title.fr')
                    ->label('Titre'),
                Tables\Columns\TextColumn::make('cta_url')
                    ->label('Lien'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\HomeSlideResource\Pages\ListHomeSlides::route('/'),
            'create' => \App\Filament\Resources\HomeSlideResource\Pages\CreateHomeSlide::route('/create'),
            'edit' => \App\Filament\Resources\HomeSlideResource\Pages\EditHomeSlide::route('/{record}/edit'),
        ];
    }
}
