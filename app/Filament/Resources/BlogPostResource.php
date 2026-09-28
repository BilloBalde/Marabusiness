<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Contenu du site';

    protected static ?string $modelLabel = 'Article';

    protected static ?string $pluralModelLabel = 'Blog';

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
                            ->helperText('Utilisé dans l\'URL : /blog/ce-texte'),
                        Forms\Components\TextInput::make('title.fr')
                            ->label('Titre')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('excerpt.fr')
                            ->label('Extrait (résumé affiché dans la liste)')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('content.fr')
                            ->label('Contenu')
                            ->required()
                            ->fileAttachmentsDisk('public_uploads')
                            ->fileAttachmentsDirectory('blog/content-images')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Médias et classement')
                    ->schema([
                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Image de couverture')
                            ->image()
                            ->disk('public_uploads')
                            ->directory('blog/covers'),
                        Forms\Components\Select::make('category_id')
                            ->label('Catégorie')
                            ->options(fn () => BlogCategory::all()->mapWithKeys(fn ($c) => [$c->id => $c->name_text]))
                            ->searchable(),
                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Publication')
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('Publié')
                            ->default(false),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Date de publication')
                            ->default(now()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('')
                    ->disk('public_uploads'),
                Tables\Columns\TextColumn::make('title.fr')
                    ->label('Titre')
                    ->searchable(query: fn ($query, string $search) => $query->where('title', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('category.name.fr')
                    ->label('Catégorie'),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publié le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('views')
                    ->label('Vues')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
