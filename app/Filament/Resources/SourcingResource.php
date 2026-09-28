<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SourcingResource\Pages;
use App\Filament\Resources\SourcingResource\RelationManagers\SourceProductsRelationManager;
use App\Models\Sourcing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SourcingResource extends Resource
{
    protected static ?string $model = Sourcing::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Sourcing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contenu (français)')
                    ->description("Anglais et chinois viendront plus tard — seul le français est demandé pour l'instant.")
                    ->schema([
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText("Utilisé dans l'URL : /sourcing/ce-texte"),
                        Forms\Components\TextInput::make('title.fr')
                            ->label('Titre')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('short_description.fr')
                            ->label('Description courte')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('description.fr')
                            ->label('Description complète')
                            ->fileAttachmentsDisk('public_uploads')
                            ->fileAttachmentsDirectory('sourcing/description-images')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Médias')
                    ->schema([
                        Forms\Components\FileUpload::make('gallery')
                            ->label('Galerie')
                            ->multiple()
                            ->disk('public_uploads')
                            ->directory('sourcing/gallery')
                            ->visibility('public')
                            ->reorderable()
                            ->panelLayout('grid')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('catalog_pdf')
                            ->label('Catalogue PDF')
                            ->disk('public_uploads')
                            ->directory('sourcing/catalogs')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf']),
                        Forms\Components\TextInput::make('icon')
                            ->helperText('Nom d\'icône Heroicon pour le menu, ex. heroicon-o-cube'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Liens externes')
                    ->schema([
                        Forms\Components\KeyValue::make('external_links')
                            ->label('')
                            ->keyLabel('Plateforme')
                            ->valueLabel('URL')
                            ->addActionLabel('Ajouter un lien')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Affichage')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Ordre')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title.fr')
                    ->label('Titre')
                    ->searchable(query: fn ($query, string $search) => $query->where('title', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('source_products_count')
                    ->label('Produits')
                    ->counts('sourceProducts'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
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
            SourceProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSourcings::route('/'),
            'create' => Pages\CreateSourcing::route('/create'),
            'edit' => Pages\EditSourcing::route('/{record}/edit'),
        ];
    }
}
