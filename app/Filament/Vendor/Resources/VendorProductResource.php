<?php

namespace App\Filament\Vendor\Resources;

use App\Models\VendorProduct;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
use App\Filament\Vendor\Resources\VendorProductResource\Pages;

class VendorProductResource extends Resource
{
    protected static ?string $model = VendorProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?string $navigationLabel = 'My Product Offers';

    /* ----------------------------------------------------------
     | Restrict query to ONLY products belonging to this vendor
     ---------------------------------------------------------- */
    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        $vendorId = $user?->vendor?->id ?? $user?->vendor_id;

        return parent::getEloquentQuery()
            ->where('vendor_id', $vendorId);
    }


    /* ----------------------------------------------------------
     | FORM (Vendor edits their offer only)
     ---------------------------------------------------------- */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function ($operation, $record) {
                                $user = Filament::auth()->user();
                                $vendorId = $user?->vendor?->id ?? $user?->vendor_id;
                                
                                if (!$vendorId) {
                                    return Product::orderBy('name')->pluck('name', 'id');
                                }
                                
                                $existingProductIds = VendorProduct::where('vendor_id', $vendorId)
                                    ->when($operation === 'edit' && $record, function ($query) use ($record) {
                                        return $query->where('id', '!=', $record->id);
                                    })
                                    ->pluck('product_id')
                                    ->toArray();
                                
                                return Product::whereNotIn('id', $existingProductIds)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->columnSpanFull()
                            ->disabled(fn ($operation) => $operation === 'edit')
                            ->helperText(function ($operation, $record) {
                                if ($operation === 'edit') {
                                    return 'You cannot change the product once created. If you need to offer a different product, create a new offer.';
                                }
                                return null;
                            }),
                    ]),

                // VARIATION SYSTEM
                Forms\Components\Section::make('Product Variations')
                    ->schema([
                        Forms\Components\Toggle::make('has_variations')
                            ->label('This product has variations (sizes, colors, etc.)')
                            ->reactive()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) {
                                    // Clear variation data when turning off variations
                                    $set('variation_matrix', []);
                                }
                            }),

                        // Variation Attributes Definition
                        Forms\Components\Repeater::make('variation_matrix')
                            ->label('Define Variation Attributes')
                            ->schema([
                                Forms\Components\TextInput::make('attribute')
                                    ->label('Attribute Name')
                                    ->required()
                                    ->placeholder('e.g., Color, Size, Storage')
                                    ->columnSpan(1),

                                Forms\Components\TagsInput::make('values')
                                    ->label('Possible Values')
                                    ->required()
                                    ->placeholder('Add values (press Enter)')
                                    ->columnSpan(2),
                            ])
                            ->columns(3)
                            ->visible(fn ($get) => $get('has_variations'))
                            ->helperText('Example: Attribute "Color" with values "Black, White, Red"')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['attribute'] ?? null),

                        // Variation Combinations Info
                        Forms\Components\Placeholder::make('variation_info')
                            ->label('Variation Setup')
                            ->content(function ($get) {
                                $matrix = $get('variation_matrix') ?? [];
                                
                                if (empty($matrix)) {
                                    return 'Define attributes above. After saving, you can set prices and stock for each variation.';
                                }

                                $combinations = self::generateCombinations($matrix);
                                $count = count($combinations);
                                
                                if ($count > 20) {
                                    return "⚠️ Warning: You've created {$count} variations! That's too many for the form. Consider reducing the number of values.";
                                }
                                
                                $sample = collect($combinations)->take(3)->map(function ($combo) {
                                    return collect($combo)->map(fn($v, $k) => "$k: $v")->join(', ');
                                })->join(' | ');
                                
                                return "Will create {$count} variations. Example: {$sample}" . ($count > 3 ? '...' : '');
                            })
                            ->visible(fn ($get) => $get('has_variations') && !empty($get('variation_matrix')))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // BASE PRICING (Always visible)
                Forms\Components\Section::make('Base Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->default(0)
                            ->visible(fn ($get) => !$get('has_variations'))
                            ->numeric()
                            ->required()
                            ->helperText(fn ($get) => 
                                $get('has_variations') 
                                ? 'Base price for reference (variations have their own prices)'
                                : 'Product selling price'
                            ),
                            
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Purchase Price')
                            ->numeric()
                            ->helperText('Your cost price for this product'),
                    ])
                    ->columns(2),

                // SIMPLE PRODUCT ONLY FIELDS (hidden when has variations)
                Forms\Components\Section::make('Simple Product Settings')
                    ->schema([
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock Quantity')
                            ->numeric()
                            ->required()
                            ->visible(fn ($get) => !$get('has_variations'))
                            ->helperText('Total available stock'),

                        Forms\Components\Toggle::make('on_sale')
                            ->label('Put on Sale')
                            ->reactive()
                            ->visible(fn ($get) => !$get('has_variations')),

                        Forms\Components\TextInput::make('sale_price')
                            ->label('Sale Price')
                            ->numeric()
                            ->visible(fn ($get) => $get('on_sale') && !$get('has_variations'))
                            ->helperText('Special sale price'),
                    ])
                    ->visible(fn ($get) => !$get('has_variations'))
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Is Active')
                    ->default(true)
                    ->columnSpanFull(),
            ]);
    }

    // Helper method to generate combinations
    public static function generateCombinations(array $matrix): array
    {
        if (empty($matrix)) return [];
        
        $attributes = [];
        foreach ($matrix as $item) {
            if (isset($item['attribute']) && isset($item['values'])) {
                $attributes[$item['attribute']] = $item['values'];
            }
        }
        
        $combinations = [[]];
        foreach ($attributes as $attribute => $values) {
            $temp = [];
            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $temp[] = $combination + [$attribute => $value];
                }
            }
            $combinations = $temp;
        }
        
        return $combinations;
    }

    /* ----------------------------------------------------------
     | TABLE (Vendor sees their SKUs)
     ---------------------------------------------------------- */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->extraAttributes([
                        'class' => 'max-w-xs whitespace-normal break-words',
                    ]),

                Tables\Columns\TextColumn::make('variations_count')
                    ->label('Variations')
                    ->counts('variations')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price_display')
                    ->label('Price')
                    ->state(function ($record) {
                        if ($record->has_variations && $record->variations()->count() > 0) {
                            $minPrice = $record->variations()->min('price');
                            $maxPrice = $record->variations()->max('price');
                            
                            if ($minPrice === $maxPrice) {
                                return number_format($minPrice, 2) . ' ' . ($record->vendor->currency->code ?? 'USD');
                            }
                            
                            return number_format($minPrice, 2) . ' - ' . number_format($maxPrice, 2) . ' ' . ($record->vendor->currency->code ?? 'USD');
                        }
                        
                        // Simple product - show regular price
                        $price = $record->sale_price ?? $record->price ?? 0;
                        return number_format($price, 2) . ' ' . ($record->vendor->currency->code ?? 'USD');
                    })
                    ->sortable(false) // Can't sort by this custom field
                    ->searchable(false),

                Tables\Columns\TextColumn::make('min_price')
                    ->label('Min Price')
                    ->state(function ($record) {
                        if ($record->has_variations) {
                            return number_format($record->variations()->min('price') ?? 0, 2);
                        }
                        return number_format($record->price ?? 0, 2);
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('max_price')
                    ->label('Max Price')
                    ->state(function ($record) {
                        if ($record->has_variations) {
                            return number_format($record->variations()->max('price') ?? 0, 2);
                        }
                        return number_format($record->price ?? 0, 2);
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Stock')
                    ->state(function ($record) {
                        if ($record->has_variations) {
                            return $record->variations()->sum('stock');
                        }
                        return $record->stock ?? 0;
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\IconColumn::make('has_variations')
                    ->boolean()
                    ->label('Has Variants')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('manage_variations')
                    ->label('Manage Variations')
                    ->icon('heroicon-o-cog')
                    ->url(fn($record) => VendorProductResource::getUrl('variations', ['record' => $record]))
                    ->visible(fn($record) => $record->has_variations),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }


    /* ----------------------------------------------------------
     | PAGES
     ---------------------------------------------------------- */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVendorProducts::route('/'),
            'create' => Pages\CreateVendorProduct::route('/create'),
            'edit'   => Pages\EditVendorProduct::route('/{record}/edit'),
            'variations' => Pages\ManageVariations::route('/{record}/variations'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Vendor\Resources\VendorProductResource\RelationManagers\VendorProductWholesalesRelationManager::class,
        ];
    }

}
