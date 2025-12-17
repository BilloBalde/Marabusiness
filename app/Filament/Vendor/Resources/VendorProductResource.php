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
                            ->options(Product::orderBy('name')->pluck('name', 'id'))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Variations')
                    ->schema([
                        Forms\Components\KeyValue::make('variation_json')
                            ->label('Variation Attributes')
                            ->keyLabel('Attribute')
                            ->valueLabel('Value')
                            ->helperText('Ex: color: Black, storage: 128GB')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Pricing & Stock')
                    ->schema([

                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->required(),

                        Forms\Components\Toggle::make('on_sale')
                            ->label('Activate Promotion')
                            ->reactive(),

                        Forms\Components\TextInput::make('sale_price')
                            ->label('Sale Price')
                            ->numeric()
                            ->visible(fn ($get) => $get('on_sale') === true),

                        Forms\Components\DatePicker::make('sale_start')
                            ->label('Sale Start')
                            ->visible(fn ($get) => $get('on_sale') === true),

                        Forms\Components\DatePicker::make('sale_end')
                            ->label('Sale End')
                            ->visible(fn ($get) => $get('on_sale') === true),

                        Forms\Components\TextInput::make('purchase_price')
                            ->numeric()
                            ->helperText('Optional internal reference.'),

                        Forms\Components\TextInput::make('stock')
                            ->numeric()
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Offer')
                            ->default(true),
                    ])
                    ->columns(2),

            ]);
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('variation_json')
                    ->label('Variation')
                    ->formatStateUsing(fn($state) =>
                        collect($state)
                            ->map(fn($v, $k) => "{$k}: {$v}")
                            ->join(', ')
                    ),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state, $record) =>
                        number_format($state, 2) . ' ' . ($record->vendor?->currency?->code ?? 'USD')
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Sale Price')
                    ->formatStateUsing(fn ($state, $record) =>
                        number_format($state, 2) . ' ' . ($record->vendor?->currency?->code ?? 'USD')
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index'  => VendorProductResource\Pages\ListVendorProducts::route('/'),
            'create' => VendorProductResource\Pages\CreateVendorProduct::route('/create'),
            'edit'   => VendorProductResource\Pages\EditVendorProduct::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Vendor\Resources\VendorProductResource\RelationManagers\VendorProductWholesalesRelationManager::class,
        ];
    }

}
