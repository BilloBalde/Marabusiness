<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $recordTitleAttribute = 'name';

    /* -------------------------------------------------------------
     | PANEL HELPERS
     | ------------------------------------------------------------- */
    protected static function isVendorPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'vendor';
    }

    protected static function vendorId(): ?int
    {
        $user = Filament::auth()->user();
        return $user?->vendor->id ?? $user?->vendor_id ?? null;
    }

    /* -------------------------------------------------------------
     | QUERY SCOPE - Restrict vendors to categories they created
     | ------------------------------------------------------------- */
    

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.categories');
    }

    public static function getNavigationBadge(): ?string
    {
        if (static::isVendorPanel()) {
            $userId = Filament::auth()->id();
            return Category::where('created_by', $userId)->count();
        }
        
        return parent::getNavigationBadge();
    }

    public static array $families = [
        'Mode',
        'Beauté',
        'Maison',
        'Cuisine',
        'Électronique',
        'Sport',
        'Accessoires',
        'Décoration',
        'Jouets & Enfants',
        'Auto & Moto',
        'Animaux',
        'Bricolage & Outils',
        'Jardin & Extérieur',
        'Bags & Luggage',
        'Santé & Bien-être',
        'Arts & Loisirs',
        'Informatique',
        'Fêtes & Événements',
        'Bébé & Puériculture',
        'Fournitures de Bureau',
        'Jeux Vidéo',
        'Équipement Industriel',
    ];

    public static function form(Form $form): Form
    {
        $isVendor = static::isVendorPanel();
        
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name_en')
                                    ->label('Name (English)')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Set $set) use ($isVendor) {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                        }
                                        // Auto-fill other languages with English value
                                        if (empty($isVendor)) {
                                            $set('name_fr', $state);
                                            $set('name_zh', $state);
                                        }
                                    }),

                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled()
                                    ->dehydrated()
                                    ->unique(Category::class, 'slug', ignoreRecord: true)
                            ]),

                        TextInput::make('family_en')
                            ->label('Family (English)')
                            ->maxLength(255), 
                    ])
                    ->columns(1),
                    
                Section::make('French Translations')
                    ->schema([
                        TextInput::make('name_fr')
                            ->label('Name (French)')
                            ->maxLength(255),
                            
                        Select::make('family_fr')
                            ->label('Family (French)')
                            ->options(array_combine(self::$families, self::$families))
                            ->required()
                            ->default('Maison')
                            ->searchable()
                            ->preload()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) use ($isVendor) {
                                // Auto-fill other languages with English value
                                if (empty($isVendor)) {
                                    $set('family_fr', $state);
                                    $set('family_zh', $state);
                                }
                            }),
                    ])
                    ->columns(2),
                    
                Section::make('Chinese Translations')
                    ->schema([
                        TextInput::make('name_zh')
                            ->label('Name (Chinese)')
                            ->maxLength(255),
                            
                        TextInput::make('family_zh')
                            ->label('Family (Chinese)')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                    
                Section::make('Media & Settings')
                    ->schema([
                        FileUpload::make('image')
                            ->disk('public_uploads')
                            ->directory('categories')
                            ->visibility('public')
                            ->columnSpanFull()
                            ->imageEditor(),

                        // Auto-set created_by for vendors, show field for admin
                        Hidden::make('created_by')
                            ->default(fn() => Filament::auth()->id())
                            ->visible($isVendor),

                        Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->visible(fn() => !$isVendor), // Only admin can change active status
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $isVendorPanel = static::isVendorPanel();
        
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => !$isVendorPanel),
                    
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('family')
                    ->label('Famille')
                    ->sortable()
                    ->searchable(),

                ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public_uploads')
                    ->circular()
                    ->size(50),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->visible(fn() => !$isVendorPanel), // Only admin sees active status
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('family')
                    ->label('Famille')
                    ->options(array_combine(self::$families, self::$families))
                    ->visible(fn() => !$isVendorPanel), // Only admin can filter by family

                Tables\Filters\Filter::make('my_categories')
                    ->label('My Categories')
                    ->query(fn(Builder $query) => $query->where('created_by', Filament::auth()->id()))
                    ->visible($isVendorPanel),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    
                    EditAction::make()
                        ->visible(function ($record) use ($isVendorPanel) {
                            $user = Filament::auth()->user();
                            
                            // Admin can edit everything
                            if (!$isVendorPanel) {
                                return true;
                            }
                            
                            // Vendor can only edit their own categories
                            return $record->created_by === $user->id;
                        }),
                        
                    DeleteAction::make()
                        ->visible(fn() => !$isVendorPanel), // Only admin can delete
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => !$isVendorPanel), // Only admin can bulk delete
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}