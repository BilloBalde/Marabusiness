<?php

namespace App\Filament\Resources;

use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationGroup = 'Contenu du site';

    protected static ?string $modelLabel = 'Témoignage';

    protected static ?string $pluralModelLabel = 'Témoignages';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                Forms\Components\TextInput::make('role')
                    ->label('Rôle / fonction')
                    ->placeholder('Ex : Étudiant en France'),
                Forms\Components\Textarea::make('message.fr')
                    ->label('Témoignage (français)')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('photo')
                    ->label('Photo')
                    ->image()
                    ->disk('public_uploads')
                    ->directory('testimonials'),
                Forms\Components\Select::make('rating')
                    ->label('Note')
                    ->options(['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'])
                    ->placeholder('Aucune'),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Ordre d\'affichage')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_featured')
                    ->label('Mis en avant sur la home')
                    ->default(false),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('')
                    ->disk('public_uploads')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rôle'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Home')
                    ->boolean(),
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
            'index' => \App\Filament\Resources\TestimonialResource\Pages\ListTestimonials::route('/'),
            'create' => \App\Filament\Resources\TestimonialResource\Pages\CreateTestimonial::route('/create'),
            'edit' => \App\Filament\Resources\TestimonialResource\Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
