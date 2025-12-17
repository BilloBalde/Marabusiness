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

        /* ==========================================================
         | ADMIN PANEL — FULL PRODUCT FORM
         ========================================================== */
        return $form->schema([
            Forms\Components\Section::make("General Info")
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set, $context) {
                            if ($context === 'create') {
                                $set('slug', \Illuminate\Support\Str::slug($state));
                            }
                        })
                        ->disabled(fn() => $isVendor),

                    TextInput::make('slug')
                        ->disabled()
                        ->required()
                        ->dehydrated()
                        ->maxLength(255)
                        ->disabled(fn() => $isVendor),

                    Select::make('category_id')
                        ->label('Category')
                        ->options(Category::orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->disabled(fn() => $isVendor),

                    Select::make('brand_id')
                        ->label('Brand')
                        ->options(Brand::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->disabled(fn() => $isVendor),

                    Forms\Components\RichEditor::make('description')
                        ->label('Product Description')
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
                        ->disabled(fn() => $isVendor),

                    FileUpload::make('images')
                        ->multiple()
                        ->disk('public_uploads')
                        ->directory('products')
                        ->visibility('public')
                        ->columnSpanFull()
                        ->disabled(fn() => $isVendor),
                ])
                ->columns(2),

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
                                ->disabled(fn() => $isVendor)
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
                                ->disabled(fn() => $isVendor)
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
                                ->disabled(fn() => $isVendor)
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
                                ->disabled(fn() => $isVendor)
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
                                ->disabled(fn() => $isVendor)
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
                                ->disabled(fn() => $isVendor)
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
                                ->disabled(fn() => $isVendor)
                                ->default(0),

                            TextInput::make('shipping_weight')
                                ->label('Shipping Weight (kg)')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(false)
                                ->helperText('Weight used for shipping calculations')
                                ->disabled(fn() => $isVendor)
                                ->default(0),
                        ])
                        ->columns(2)
                        ->visible(fn($get) => $get('length') && $get('width') && $get('height')),
                ])
                ->collapsible()
                ->collapsed(fn() => !static::isVendorPanel())
                ->disabled(fn() => $isVendor)
                ->afterStateHydrated(function ($state, Forms\Set $set, Forms\Get $get) {
                    // Initialize calculated fields when section loads
                    self::updateCalculatedFields($set, $get);
                }),

            // Status Section
            Forms\Components\Section::make("Status")
                ->schema([
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->disabled(fn() => $isVendor),

                    Toggle::make('is_featured')
                        ->label('Featured')
                        ->disabled(fn() => $isVendor),

                    Toggle::make('in_stock')
                        ->label('In Stock')
                        ->default(true)
                        ->disabled(fn() => $isVendor),

                    Toggle::make('on_sale')
                        ->label('On Sale')
                        ->disabled(fn() => $isVendor),
                ])
                ->columns(4)
                ->disabled(fn() => $isVendor),
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
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->disk('public_uploads')
                    ->visibility('public')
                    ->circular()
                    ->size(50),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('category.name'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->getStateUsing(function (Product $record) {

                        // ================
                        // Vendor Panel
                        // ================
                        if (ProductResource::isVendorPanel()) {
                            $vendorId = ProductResource::vendorId();

                            $vendor = $record->vendors()
                                ->where('vendors.id', $vendorId)
                                ->with('currency')
                                ->first();

                            if ($vendor) {
                                return number_format($vendor->pivot->price, 2) . ' ' .
                                    ($vendor->currency?->code ?? 'USD');
                            }
                        }

                        // ================
                        // Admin Panel - take first vendor price
                        // ================
                        $vendor = $record->vendors()
                            ->with('currency')
                            ->first();

                        if ($vendor) {
                            return number_format($vendor->pivot->price, 2) . ' ' .
                                ($vendor->currency?->code ?? 'USD');
                        }

                        // ================
                        // Fallback – product base price
                        // ================
                        return number_format($record->price, 2) . ' USD';
                    })
                    ->sortable(),
                // NEW: Shipping Columns
                Tables\Columns\TextColumn::make('weight_formatted')
                    ->label('Weight')
                    ->sortable(['weight']) // Sort by the actual weight field
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('dimensions_formatted')
                    ->label('Dimensions')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cbm_formatted')
                    ->label('CBM')
                    ->sortable(['cbm']) // Sort by the actual cbm field
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('on_sale')
                    ->label('Sale')
                    ->boolean(),
                // ⭐ show is_active
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                // ⭐ show is_featured
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),

                // ⭐ show in_stock
                Tables\Columns\IconColumn::make('in_stock')
                    ->label('In Stock')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            // allows selecting rows
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                BulkAction::make('edit')
                ->label('Bulk Edit')
                ->icon('heroicon-o-pencil-square')
                ->form([
                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->options(\App\Models\Category::pluck('name', 'id'))
                        ->searchable(),

                    Forms\Components\Select::make('brand_id')
                        ->label('Brand')
                        ->options(\App\Models\Brand::pluck('name', 'id'))
                        ->searchable(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active'),

                    Forms\Components\Toggle::make('is_featured')
                        ->label('Featured'),
                        Forms\Components\RichEditor::make('description')
                        ->label('Product Description')
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
                        ]),
                ])
                ->action(function (array $data, $records) {

                    // CLEAN THE DATA — remove null values so we don't overwrite with null
                    $cleanData = collect($data)->filter(fn ($v) => $v !== null)->toArray();

                    if (empty($cleanData)) {
                        return;
                    }

                    foreach ($records as $record) {
                        $record->update($cleanData);
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Bulk Edit Products')
                ->modalSubmitActionLabel('Update Selected'),
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