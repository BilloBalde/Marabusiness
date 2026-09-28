<?php

namespace App\Filament\Resources;

use App\Models\BlogCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Contenu du site';

    protected static ?string $modelLabel = 'Catégorie de blog';

    protected static ?string $pluralModelLabel = 'Catégories de blog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name.fr')
                    ->label('Nom (français)')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name.fr')
                    ->label('Nom'),
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('posts_count')
                    ->label('Articles')
                    ->counts('posts'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => \App\Filament\Resources\BlogCategoryResource\Pages\ListBlogCategories::route('/'),
            'create' => \App\Filament\Resources\BlogCategoryResource\Pages\CreateBlogCategory::route('/create'),
            'edit' => \App\Filament\Resources\BlogCategoryResource\Pages\EditBlogCategory::route('/{record}/edit'),
        ];
    }
}
