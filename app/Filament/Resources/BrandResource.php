<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.brands');
    }

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

    protected static ?string $recordTitleAttribute = 'name';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    Grid::make()
                        ->schema([
                            Tabs::make('Translations')
                                ->columns(1)
                                ->tabs([
                                    Tab::make('EN')->schema([
                                        TextInput::make('name_en')
                                            ->label('Name (EN)')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(string $operation, $state, Set $set) =>
                                                $operation === 'create' ? $set('slug', Str::slug($state)) : null
                                            ),
                                    ]),
                                    Tab::make('FR')->schema([
                                        TextInput::make('name_fr')->label('Nom (FR)'),
                                    ]),
                                    Tab::make('ZH')->schema([
                                        TextInput::make('name_zh')->label('名称 (ZH)'),
                                    ]),
                                ]),


                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated()
                                ->unique(Brand::class, 'slug', ignoreRecord: true)
                        ]),
                    FileUpload::make('image')
                        ->disk('public_uploads')
                        ->directory('brands')
                        ->visibility('public'),

                    Toggle::make('is_active')
                        ->required()
                        ->default(true)
                ])
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            $locale   = app()->getLocale();
                            $fallback = config('app.fallback_locale', 'en');

                            return $query->where(function ($q) use ($search, $locale, $fallback) {

                                // 1️⃣ search translated value (current locale)
                                $q->whereHas('translations', function ($t) use ($search, $locale) {
                                    $t->where('locale', $locale)
                                    ->where('name', 'like', "%{$search}%");
                                })

                                // 2️⃣ fallback locale
                                ->orWhereHas('translations', function ($t) use ($search, $fallback) {
                                    $t->where('locale', $fallback)
                                    ->where('name', 'like', "%{$search}%");
                                })

                                // 3️⃣ base column (EN / raw)
                                ->orWhere('name', 'like', "%{$search}%");
                            });
                        }
                    ),
                Tables\Columns\ImageColumn::make('image')
                    ->disk('public_uploads'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
                //
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
                        ->visible(fn() => !$isVendorPanel),
                ])
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
