<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\VendorProduct;
use Filament\Tables\Enums\RecordCheckboxPosition;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    

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

    /**
     * A vendor owns a product if they authored the catalog entry (created_by) OR
     * they actually have a vendor_product listing for it. Used consistently by
     * getEloquentQuery() above, the Edit action's visibility, and
     * EditProduct::authorizeAccess() — the same three places that used to check
     * created_by alone, which is why fixing only the query would have left a
     * vendor able to see a product in their list without being able to open it.
     */
    public static function ownedByVendor(Product $record, ?int $userId, ?int $vendorId): bool
    {
        if ($userId !== null && $record->created_by === $userId) {
            return true;
        }

        return $vendorId !== null && $record->vendorProducts()->where('vendor_id', $vendorId)->exists();
    }

    /**
     * The list, search and record count were never scoped — only the Edit action's
     * visibility and EditProduct::authorizeAccess() restricted what a vendor could
     * touch, not what they could see. A vendor opening "Produits" saw every product
     * from every vendor on the marketplace.
     *
     * Scoped by created_by OR an actual vendor_product listing — not created_by
     * alone. Real ownership in this marketplace is the vendor_product pivot (that's
     * what the storefront, the price column below, and checkout all read); created_by
     * only records who typed the catalog entry in, which can be an admin, or nobody
     * at all if a listing was ever attached to a product created by someone else. A
     * real product ("tshitNike", created_by NULL) sold by OmarShop was invisible in
     * OmarShop's own "Produits" tab because of this. created_by still counts too:
     * a vendor who has just created a product has no vendor_product row yet, so
     * dropping created_by entirely would make their own new product disappear from
     * their own list the moment ownership stopped being "who typed it in".
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (static::isVendorPanel()) {
            $vendorId = static::vendorId();

            $query->where(function (Builder $q) use ($vendorId) {
                $q->where('created_by', Filament::auth()->id())
                    ->orWhereHas('vendorProducts', fn (Builder $vp) => $vp->where('vendor_id', $vendorId));
            });
        }

        return $query;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.products');
    }


    /* -------------------------------------------------------------
     | FORM
     | ------------------------------------------------------------- */
    public static function form(Form $form): Form
    {
        $isVendor = static::isVendorPanel();
        $userId = Filament::auth()->id();

        /* ==========================================================
         | ADMIN PANEL — FULL PRODUCT FORM
         ========================================================== */
        return $form->schema([
            Forms\Components\Section::make("General Info")
                ->schema([
                    Tabs::make('Translations')
                        ->columnSpanFull()
                        ->tabs([
                            Tab::make('EN')->schema([
                                TextInput::make('name_en')
                                ->label('Name (EN)')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                    if ($operation === 'create') {
                                        $set('slug', Str::slug($state));
                                    }
                                }),

                            Textarea::make('short_description_en')
                                ->label('Short Description (EN)')
                                ->rows(3),

                            Forms\Components\RichEditor::make('description_en')
                                ->label('Description (EN)')
                                ->columnSpanFull()
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'strike',
                                    'underline',
                                    'bulletList',
                                    'orderedList',
                                    'link',
                                    'blockquote',
                                    'codeBlock',
                                    'redo',
                                    'undo',
                                ])
                                ->maxLength(5000)
                                ->extraInputAttributes(['style' => 'min-height: 200px;']),
                            ]),

                            Tab::make('FR')->schema([
                                TextInput::make('name_fr')->label('Nom (FR)'),
                                Textarea::make('short_description_fr')->label('Description courte (FR)')->rows(3),
                                Forms\Components\RichEditor::make('description_fr')
                                    ->label('Description (FR)')
                                    ->columnSpanFull()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'strike',
                                        'underline',
                                        'bulletList',
                                        'orderedList',
                                        'link',
                                        'blockquote',
                                        'codeBlock',
                                        'redo',
                                        'undo',
                                    ])
                                    ->maxLength(5000)
                                    ->extraInputAttributes(['style' => 'min-height: 200px;']),
                            ]),

                            Tab::make('ZH')->schema([
                                TextInput::make('name_zh')->label('名称 (ZH)'),
                                Textarea::make('short_description_zh')->label('短描述 (ZH)')->rows(3),
                                Forms\Components\RichEditor::make('description_zh')
                                    ->label('描述 (ZH)')
                                    ->columnSpanFull()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'strike',
                                        'underline',
                                        'bulletList',
                                        'orderedList',
                                        'link',
                                        'blockquote',
                                        'codeBlock',
                                        'redo',
                                        'undo',
                                    ])
                                    ->maxLength(5000)
                                    ->extraInputAttributes(['style' => 'min-height: 200px;']),
                            ]),
                    ]),

                    TextInput::make('slug')
                        ->disabled()
                        ->required()
                        ->dehydrated()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->unique(Product::class, 'slug', ignoreRecord: true),

                    Select::make('category_id')
                        ->label('Category')
                        ->options(Category::orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        /* ->createOptionForm([
                            TextInput::make('name_en')
                                ->label('Name (EN)')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set) {
                                    $set('slug', Str::slug($state));
                                }),
                            TextInput::make('name_fr')
                                ->label('Name (FR)'),
                            TextInput::make('name_zh')
                                ->label('Name (ZH)'),
                            TextInput::make('slug')
                                ->required()
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make('family_en')
                                ->label('Family (EN)')
                                ->maxLength(255),
                            TextInput::make('family_fr')
                                ->label('Family (FR)')
                                ->maxLength(255),
                            TextInput::make('family_zh')
                                ->label('Family (ZH)')
                                ->maxLength(255),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $data['created_by'] = Filament::auth()->id();
                            $data['name'] = $data['name_en'] ?? $data['name'] ?? null;
                            $data['family'] = $data['family_en'] ?? $data['family'] ?? null;

                            $category = Category::create($data);
                            $category->syncTranslations([
                                'en' => [
                                    'name' => $data['name_en'] ?? null,
                                    'family' => $data['family_en'] ?? null,
                                ],
                                'fr' => [
                                    'name' => $data['name_fr'] ?? null,
                                    'family' => $data['family_fr'] ?? null,
                                ],
                                'zh' => [
                                    'name' => $data['name_zh'] ?? null,
                                    'family' => $data['family_zh'] ?? null,
                                ],
                            ]);

                            return $category->getKey();
                        }) */,

                    Select::make('brand_id')
                        ->label('Brand')
                        ->options(Brand::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('name_en')
                                ->label('Name (EN)')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set) {
                                    $set('slug', Str::slug($state));
                                }),
                            TextInput::make('name_fr')
                                ->label('Name (FR)'),
                            TextInput::make('name_zh')
                                ->label('Name (ZH)'),
                            TextInput::make('slug')
                                ->required()
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $data['created_by'] = Filament::auth()->id();
                            $data['name'] = $data['name_en'] ?? $data['name'] ?? null;

                            $brand = Brand::create($data);
                            $brand->syncTranslations([
                                'en' => [
                                    'name' => $data['name_en'] ?? null,
                                ],
                                'fr' => [
                                    'name' => $data['name_fr'] ?? null,
                                ],
                                'zh' => [
                                    'name' => $data['name_zh'] ?? null,
                                ],
                            ]);

                            return $brand->getKey();
                        }),

                    FileUpload::make('images')
                        ->multiple()
                        ->disk('public_uploads')
                        ->directory('products')
                        ->visibility('public')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            // Video Section
            Forms\Components\Section::make('Product Video')
                ->description('Add a product demonstration video')
                ->schema([
                    Forms\Components\FileUpload::make('video')
                        ->label('Upload Video')
                        ->disk('public_uploads')
                        ->directory('products/videos')
                        ->visibility('public')
                        ->maxSize(51200)
                        ->helperText('Upload any video file (MP4, MOV, AVI, etc.) up to 50MB')
                        ->reactive(),
                        
                    Forms\Components\TextInput::make('video_url')
                        ->label('OR Video URL')
                        ->placeholder('https://youtube.com/watch?v=... or https://vimeo.com/...')
                        ->url()
                        ->helperText('YouTube or Vimeo URL'),
                        
                    Forms\Components\FileUpload::make('video_thumbnail')
                        ->label('Video Thumbnail')
                        ->disk('public_uploads')
                        ->directory('products/video-thumbnails')
                        ->visibility('public')
                        ->visible(fn ($get) => $get('video_url') || $get('video')),
                ])->collapsible(),

            // Description Images Section
            Forms\Components\Section::make('Description Images')
                ->description('Images that appear within the product description')
                ->schema([
                    Forms\Components\FileUpload::make('description_images')
                        ->multiple()
                        ->disk('public_uploads')
                        ->directory('products/description-images')
                        ->visibility('public')
                        ->columnSpanFull()
                        ->maxFiles(20)
                        ->reorderable()
                        ->panelLayout('grid')
                        ->helperText('These images can be inserted into the rich text description'),
                ])->collapsed(),

            // NEW: Shipping & Dimensions Section
            Forms\Components\Section::make("Shipping & Dimensions")
                ->schema([
                    // Weight Fields
                    Forms\Components\Grid::make()
                        ->schema([
                            TextInput::make('weight')
                                ->label('Weight')
                                ->numeric()
                                ->step(0.01)
                                ->suffix(fn($get) => $get('weight_unit') ?? 'kg')
                                ->helperText('Product weight for shipping calculations')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    self::updateCalculatedFields($set, $get);
                                }),

                            Select::make('weight_unit')
                                ->label('Weight Unit')
                                ->options([
                                    'kg' => 'Kilograms (kg)',
                                    'g' => 'Grams (g)',
                                    'lb' => 'Pounds (lb)',
                                    'oz' => 'Ounces (oz)',
                                ])
                                ->default('kg')
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    self::updateCalculatedFields($set, $get);
                                }),
                        ])
                        ->columns(2),

                    // Dimension Fields
                    Forms\Components\Grid::make()
                        ->schema([
                            TextInput::make('length')
                                ->label('Length')
                                ->numeric()
                                ->step(0.01)
                                ->suffix(fn($get) => $get('dimension_unit') ?? 'cm')
                                ->helperText('Product length')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    self::updateCalculatedFields($set, $get);
                                }),

                            TextInput::make('width')
                                ->label('Width')
                                ->numeric()
                                ->step(0.01)
                                ->suffix(fn($get) => $get('dimension_unit') ?? 'cm')
                                ->helperText('Product width')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    self::updateCalculatedFields($set, $get);
                                }),

                            TextInput::make('height')
                                ->label('Height')
                                ->numeric()
                                ->step(0.01)
                                ->suffix(fn($get) => $get('dimension_unit') ?? 'cm')
                                ->helperText('Product height')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    self::updateCalculatedFields($set, $get);
                                }),

                            Select::make('dimension_unit')
                                ->label('Dimension Unit')
                                ->options([
                                    'cm' => 'Centimeters (cm)',
                                    'm' => 'Meters (m)',
                                    'in' => 'Inches (in)',
                                    'ft' => 'Feet (ft)',
                                ])
                                ->default('cm')
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    self::updateCalculatedFields($set, $get);
                                }),
                        ])
                        ->columns(2),

                    // Calculated Fields (Read-only)
                    Forms\Components\Grid::make()
                        ->schema([
                            TextInput::make('cbm')
                                ->label('CBM (Cubic Meters)')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(false)
                                ->helperText('Automatically calculated from dimensions')
                                ->default(0),

                            TextInput::make('shipping_weight')
                                ->label('Shipping Weight (kg)')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(false)
                                ->helperText('Weight used for shipping calculations')
                                ->default(0),
                        ])
                        ->columns(2)
                        ->visible(fn($get) => $get('length') && $get('width') && $get('height')),
                ])
                ->collapsible()
                ->collapsed(fn() => !static::isVendorPanel())
                ->afterStateHydrated(function ($state, Forms\Set $set, Forms\Get $get) {
                    // Initialize calculated fields when section loads
                    self::updateCalculatedFields($set, $get);
                }),

            // Status Section
            Forms\Components\Section::make("Status")
                ->schema([
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Toggle::make('is_featured')
                        ->label('Featured')
                        ->default(true),

                    Toggle::make('in_stock')
                        ->label('In Stock')
                        ->default(true),

                    Toggle::make('on_sale')
                        ->label('On Sale')
                        ->default(false),
                ])
                ->columns(4),
        ]);
    }

    /**
     * Calculate CBM from dimensions
     */
    private static function calculateCBM($length, $width, $height, $dimensionUnit)
    {
        if (!$length || !$width || !$height) {
            return 0;
        }

        // Convert to meters
        $lengthM = self::convertToMeters($length, $dimensionUnit);
        $widthM = self::convertToMeters($width, $dimensionUnit);
        $heightM = self::convertToMeters($height, $dimensionUnit);
        
        return round($lengthM * $widthM * $heightM, 4);
    }

    /**
     * Calculate shipping weight (actual or volumetric, whichever is greater)
     */
    private static function calculateShippingWeight($weight, $weightUnit, $length, $width, $height, $dimensionUnit)
    {
        // Convert actual weight to kg
        $actualWeightKg = self::convertWeightToKg($weight, $weightUnit);
        
        // Calculate volumetric weight
        $cbm = self::calculateCBM($length, $width, $height, $dimensionUnit);
        $volumetricWeightKg = $cbm * 167; // Standard conversion factor
        
        // Use the greater of actual or volumetric weight
        return round(max($actualWeightKg, $volumetricWeightKg), 2);
    }

    /**
     * Convert weight to kilograms
     */
    private static function convertWeightToKg($weight, $unit)
    {
        if (!$weight) return 0;
        
        return match($unit) {
            'kg' => $weight,
            'g' => $weight / 1000,
            'lb' => $weight * 0.453592,
            'oz' => $weight * 0.0283495,
            default => $weight,
        };
    }

    /**
     * Convert dimension to meters for CBM calculation
     */
    private static function convertToMeters($value, $unit)
    {
        if (!$value) return 0;
        
        return match($unit) {
            'cm' => $value / 100,
            'm' => $value,
            'in' => $value * 0.0254,
            'ft' => $value * 0.3048,
            default => $value / 100, // default to cm conversion
        };
    }

    /**
     * Update calculated fields when dimensions change
     */
    private static function updateCalculatedFields(Forms\Set $set, Forms\Get $get): void
    {
        $length = $get('length');
        $width = $get('width');
        $height = $get('height');
        $dimensionUnit = $get('dimension_unit') ?? 'cm';
        $weight = $get('weight');
        $weightUnit = $get('weight_unit') ?? 'kg';

        // Update CBM
        if ($length && $width && $height) {
            $cbm = self::calculateCBM($length, $width, $height, $dimensionUnit);
            $set('cbm', $cbm);
            
            // Update shipping weight
            $shippingWeight = self::calculateShippingWeight($weight, $weightUnit, $length, $width, $height, $dimensionUnit);
            $set('shipping_weight', $shippingWeight);
        } else {
            $set('cbm', 0);
            $set('shipping_weight', 0);
        }
    }

    /* -------------------------------------------------------------
     | AFTER SAVE - Calculate CBM and shipping weight
     | ------------------------------------------------------------- */
    public static function afterSave(Product $record, array $data): void
    {
        // Calculate and save CBM if dimensions are provided
        if ($record->length && $record->width && $record->height) {
            $cbm = self::calculateCBM(
                $record->length,
                $record->width,
                $record->height,
                $record->dimension_unit ?? 'cm'
            );
            $record->update(['cbm' => $cbm]);
        }
    }

    /* -------------------------------------------------------------
     | TABLE
     | ------------------------------------------------------------- */
    public static function table(Table $table): Table
    {
        $isVendorPanel = static::isVendorPanel();
        
        return $table
            ->columns([
                // Show creator name only in admin panel
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => !$isVendorPanel),
                    
                Tables\Columns\ImageColumn::make('images')
                    ->disk('public_uploads')
                    ->visibility('public')
                    ->circular()
                    ->size(50),
                    
                Tables\Columns\TextColumn::make('name')
                    ->wrap() // Uses 'wrap' instead of 'wordwrap' (Filament 3+)
                    ->extraAttributes([
                        'style' => 'max-width: 300px; white-space: normal;'
                    ])
                    ->sortable()
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            $locale   = app()->getLocale();
                            $fallback = config('app.fallback_locale', 'en');

                            return $query->where(function ($q) use ($search, $locale, $fallback) {
                                $q->whereHas('translations', function ($t) use ($search, $locale) {
                                    $t->where('locale', $locale)
                                    ->where('name', 'like', "%{$search}%");
                                })
                                ->orWhereHas('translations', function ($t) use ($search, $fallback) {
                                    $t->where('locale', $fallback)
                                    ->where('name', 'like', "%{$search}%");
                                })
                                ->orWhere('name', 'like', "%{$search}%");
                            });
                        }
                    ),
                    
                Tables\Columns\TextColumn::make('category.name'),
                Tables\Columns\TextColumn::make('brand.name'),
                Tables\Columns\TextColumn::make('short_description')
                    ->wrap() // Uses 'wrap' instead of 'wordwrap' (Filament 3+)
                    ->extraAttributes([
                        'style' => 'max-width: 200px; white-space: normal;'
                    ]),
                    
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->getStateUsing(function (Product $record) use ($isVendorPanel) {
                        // Vendor Panel - show their price
                        if ($isVendorPanel) {
                            $vendorId = static::vendorId();
                            $vendor = $record->vendors()
                                ->where('vendors.id', $vendorId)
                                ->with('currency')
                                ->first();

                            if ($vendor) {
                                return number_format($vendor->pivot->price, 2) . ' ' .
                                    ($vendor->currency?->code ?? 'USD');
                            }
                        }

                        // Admin Panel - show first vendor price or base price
                        $vendor = $record->vendors()->with('currency')->first();
                        if ($vendor) {
                            return number_format($vendor->pivot->price, 2) . ' ' .
                                ($vendor->currency?->code ?? 'USD');
                        }

                        return number_format($record->price, 2) . ' USD';
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('weight_formatted')
                    ->label('Weight')
                    ->sortable(['weight'])
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('dimensions_formatted')
                    ->label('Dimensions')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cbm_formatted')
                    ->label('CBM')
                    ->sortable(['cbm'])
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('on_sale')
                    ->label('Sale')
                    ->boolean(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),

                Tables\Columns\IconColumn::make('in_stock')
                    ->label('In Stock')
                    ->boolean(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color('gray'),
                    Tables\Actions\EditAction::make()
                        ->visible(function ($record) use ($isVendorPanel) {
                            // Admin can edit everything
                            if (!$isVendorPanel) {
                                return true;
                            }

                            // Vendor can only edit products they created or list.
                            return static::ownedByVendor($record, Filament::auth()->id(), static::vendorId());
                        }),
                        
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn() => !$isVendorPanel), // Only admin can delete
                ])
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn() => !static::isVendorPanel()), // Only admin can bulk delete
                    
                BulkAction::make('edit')
                    ->label('Bulk Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(Category::pluck('name', 'id'))
                            ->searchable(),

                        Forms\Components\Select::make('brand_id')
                            ->label('Brand')
                            ->options(Brand::pluck('name', 'id'))
                            ->searchable(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->visible(fn() => !static::isVendorPanel()),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
                            ->visible(fn() => !static::isVendorPanel()),
                            
                        Forms\Components\RichEditor::make('description')
                            ->label('Product Description')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold', 'italic', 'strike', 'underline',
                                'bulletList', 'orderedList', 'link',
                                'blockquote', 'codeBlock', 'redo', 'undo',
                            ]),
                    ])
                    ->action(function (array $data, $records) {
                        $cleanData = collect($data)->filter(fn ($v) => $v !== null)->toArray();
                        if (empty($cleanData)) return;

                        foreach ($records as $record) {
                            $record->update($cleanData);
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Bulk Edit Products')
                    ->modalSubmitActionLabel('Update Selected')
                    ->visible(fn() => !static::isVendorPanel()), // Only admin can bulk edit
            ]);
    }

    /* -------------------------------------------------------------
     | PAGES
     | ------------------------------------------------------------- */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view'   => Pages\ViewProduct::route('/{record}'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ProductResource\RelationManagers\VendorProductReviewsRelationManager::class,
            \App\Filament\Resources\ProductResource\RelationManagers\VendorProductsRelationManager::class,
        ];
    }
}
