<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function shouldRegisterNavigation(): bool
    {
        // Hide from admin navigation
        return false;
    }

    public static function canViewAny(): bool
    {
        // Disable index access
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.purchases');
    }

    /* =============================================================
     * HELPERS
     * ============================================================= */

    protected static function isVendorPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'vendor';
    }

    protected static function getVendorId(): ?int
    {
        $user = Filament::auth()->user();
        if (! $user) return null;

        if ($user instanceof \App\Models\Vendor) return $user->id;
        if (method_exists($user, 'vendor') && $user->vendor) return $user->vendor->id;

        return $user->vendor_id ?? null;
    }

    protected static function resolveVendor(?int $vendorId): ?Vendor
    {
        if (! $vendorId) return null;
        return Vendor::with('currency')->find($vendorId);
    }

    /* =============================================================
     * FORM
     * ============================================================= */

    public static function form(Form $form): Form
    {
        return $form->schema([

            /* ===================================================== */
            /* PURCHASE INFORMATION                                   */
            /* ===================================================== */
            Section::make("Purchase Info")
                ->schema([

                    TextInput::make('reference')
                        ->default(fn () => Purchase::generateReference())
                        ->readOnly()
                        ->dehydrated(),

                    Select::make('vendor_id')
                        ->label('Vendor')
                        ->hidden(fn () => static::isVendorPanel())
                        ->default(fn () => static::getVendorId())
                        ->disabled(fn () => static::isVendorPanel())
                        ->options(Vendor::orderBy('store_name')->pluck('store_name', 'id'))
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, $state) {
                            $set('items', []);
                            $set('total_cost', 0);
                            $set('total_quantity', 0);

                            $currencyId = Vendor::where('id', $state)->value('currency_id');
                            $set('currency_id', $currencyId);
                        }),

                    Select::make('supplier_id')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'ordered' => 'Ordered',
                            'received' => 'Received',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('draft'),

                    Placeholder::make('currency')
                        ->label('Currency')
                        ->content(function (Get $get) {
                            $vendor = static::resolveVendor($get('vendor_id'));
                            return $vendor?->currency?->code
                                ?? $vendor?->currency?->symbol
                                ?? '---';
                        }),

                    Hidden::make('currency_id')
                        ->dehydrated()
                        ->default(fn (Get $get) => static::resolveVendor($get('vendor_id'))?->currency_id),
                ])
                ->columns(2),

            /* ===================================================== */
            /* ITEMS SECTION                                          */
            /* ===================================================== */
            Section::make("Items")
                ->schema([

                    Repeater::make('items')
                        ->relationship('items')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->live()
                        ->columns(6)
                        ->afterStateHydrated(fn ($state, Set $set) => static::recalculateTotals($state ?? [], $set))
                        ->afterStateUpdated(fn ($state, Set $set) => static::recalculateTotals($state ?? [], $set))
                        ->schema([

                            /* -------------------------------------------- */
                            /* PRODUCT SELECT                               */
                            /* -------------------------------------------- */
                            Select::make('product_id')
                                ->label('Product')
                                ->options(Product::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->reactive()

                                /* ------------------------------------------------------
                                * 1️⃣ HYDRATE EXISTING vendor_product WHEN EDITING
                                * ------------------------------------------------------ */
                                ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                    $vendorId  = $get('../../vendor_id');
                                    $productId = $state;

                                    if (! $vendorId || ! $productId) return;

                                    $vp = VendorProduct::where('vendor_id', $vendorId)
                                        ->where('product_id', $productId)
                                        ->first();

                                    if ($vp) {
                                        $set('vendor_product_id', $vp->id);
                                        $set('stock', $vp->stock);
                                        $set('purchase_price', $vp->purchase_price ?? 0);
                                        $set('price', $vp->price ?? 0);
                                    }
                                })

                                /* ------------------------------------------------------
                                * 2️⃣ ON CHANGE: create vendor_product if missing
                                * ------------------------------------------------------ */
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $vendorId  = $get('../../vendor_id');
                                    $productId = $state;

                                    if (! $vendorId || ! $productId) return;

                                    $vp = VendorProduct::firstOrCreate(
                                        [
                                            'vendor_id'  => $vendorId,
                                            'product_id' => $productId,
                                        ],
                                        [
                                            'price'            => 0,
                                            'purchase_price'   => 0,
                                            'sale_price'       => 0,
                                            'discount_percent' => 0,
                                            'stock'            => 0,
                                        ]
                                    );

                                    $set('vendor_product_id', $vp->id);
                                    $set('stock', $vp->stock);
                                })

                                /* ------------------------------------------------------
                                * 3️⃣ CREATE PRODUCT ON THE FLY
                                * ------------------------------------------------------ */
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')->required(),

                                    Forms\Components\Select::make('category_id')
                                        ->options(\App\Models\Category::pluck('name', 'id'))
                                        ->required(),

                                    Forms\Components\Select::make('brand_id')
                                        ->options(\App\Models\Brand::pluck('name', 'id'))
                                        ->required(),

                                    Forms\Components\TextInput::make('price')
                                        ->numeric()
                                        ->required()
                                        ->label('Base Price'),

                                    Forms\Components\Radio::make('on_sale')
                                        ->label('Discount / Promotion')
                                        ->options([
                                            false => 'No promotion',
                                            true  => 'Activate Promotion',
                                        ])
                                        ->default(false)
                                        ->inline()
                                        ->reactive(),

                                    Forms\Components\TextInput::make('sale_price')
                                        ->label('Sale Price')
                                        ->numeric()
                                        ->visible(fn ($get) => (bool)$get('on_sale') === true),

                                    Forms\Components\DatePicker::make('sale_start')
                                        ->visible(fn ($get) => (bool)$get('on_sale') === true),

                                    Forms\Components\DatePicker::make('sale_end')
                                        ->visible(fn ($get) => (bool)$get('on_sale') === true),
                                ])

                                ->createOptionUsing(function (array $data, Get $get, Set $set) {
                                    $vendorId = $get('../../vendor_id');
                                    if (! $vendorId) {
                                        throw \Illuminate\Validation\ValidationException::withMessages([
                                            'vendor_id' => "Select a vendor first."
                                        ]);
                                    }

                                    $onSale = !empty($data['on_sale']) && $data['on_sale'];

                                    /* 🔵 1) CREATE PRODUCT */
                                    $product = Product::create([
                                        'name' => $data['name'],
                                        'slug' => \Str::slug($data['name']) . "-" . \Str::random(4),
                                        'category_id' => $data['category_id'],
                                        'brand_id' => $data['brand_id'],
                                        'price' => $data['price'],
                                        'on_sale' => $onSale,
                                    ]);

                                    /* 🔵 2) SALE DETAILS */
                                    $salePrice = $onSale ? ($data['sale_price'] ?? null) : null;
                                    $discount = null;

                                    if (!empty($data['sale_price']) && $data['sale_price'] < $data['price']) {
                                        $discount = round(100 - ($data['sale_price'] / $data['price'] * 100));
                                    }

                                    /* 🔵 3) CREATE vendor_product */
                                    $vp = VendorProduct::create([
                                        'vendor_id'        => $vendorId,
                                        'product_id'       => $product->id,
                                        'price'            => $data['price'],
                                        'sale_price'       => $salePrice,
                                        'discount_percent' => $discount,
                                        'sale_start'       => $data['sale_start'] ?? null,
                                        'sale_end'         => $data['sale_end'] ?? null,
                                        'stock'            => 0,
                                    ]);

                                    /* 🔵 HYDRATE the repeater row */
                                    $set('vendor_product_id', $vp->id);
                                    $set('product_id', $product->id);
                                    $set('purchase_price', $data['price']);  
                                    $set('price', $data['price']);
                                    $set('stock', 0);

                                    return $product->id;
                                }),
                            Hidden::make('vendor_product_id')->dehydrated(),

                            TextInput::make('stock')
                                ->label('Vendor Stock')
                                ->readOnly()
                                ->suffix('pcs'),

                            /* -------------------------------------------- */
                            /* PURCHASE PRICE (unit cost)                   */
                            /* -------------------------------------------- */
                            TextInput::make('purchase_price')
                                ->label('Purchase Price')
                                ->numeric()
                                ->required()
                                ->dehydrated()
                                ->afterStateHydrated(fn (Set $set, $state) => $set('purchase_price', $state))
                                ->reactive()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $qty = (int) $get('quantity');
                                    $set('total_cost_items', $qty * (float) $get('purchase_price'));
                                }),

                            /* -------------------------------------------- */
                            /* SALE PRICE                                   */
                            /* -------------------------------------------- */
                            TextInput::make('price')
                                ->label('Sale Price')
                                ->numeric()
                                ->required()
                                ->dehydrated()
                                ->afterStateHydrated(fn (Set $set, $state) => $set('price', $state)),

                            /* -------------------------------------------- */
                            /* QUANTITY                                     */
                            /* -------------------------------------------- */
                            TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->reactive()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $qty = (int) $get('quantity');
                                    $cost = (float) $get('purchase_price');
                                    $set('total_cost_items', $qty * $cost);
                                }),

                            TextInput::make('total_cost_items')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(),
                        ]),
                ]),

            /* ===================================================== */
            /* TOTALS                                                 */
            /* ===================================================== */
            Section::make("Totals")
                ->schema([
                    TextInput::make('total_quantity')->readOnly()->dehydrated(),
                    TextInput::make('total_cost')->readOnly()->dehydrated(),
                ])
                ->columns(2),
        ]);
    }

    /* =============================================================
     * TOTALS CALCULATOR
     * ============================================================= */
    protected static function recalculateTotals(array $items, Set $set): void
    {
        $qty = 0;
        $cost = 0;

        foreach ($items as $item) {
            $qty += (int) ($item['quantity'] ?? 0);
            $cost += (float) ($item['total_cost_items'] ?? 0);
        }

        $set('total_quantity', $qty);
        $set('total_cost', $cost);
    }

    /* =============================================================
     * AFTER SAVE — UPDATE STOCK & PRICES
     * ============================================================= */
    public static function afterSave(Purchase $purchase): void
    {
        foreach ($purchase->items as $item) {

            $vp = VendorProduct::find($item->vendor_product_id);
            if (! $vp) continue;

            // Update prices
            $vp->purchase_price = $item->purchase_price;
            $vp->price = $item->price;

            // Update stock only when received
            if ($purchase->status === 'received') {
                $vp->stock += $item->quantity;
            }

            $vp->save();
        }
    }

    public static function preparePurchaseData(array $data, ?Purchase $record = null): array
    {
        // ✅ Ensure vendor_id is set from the logged-in user if missing
        if (! ($data['vendor_id'] ?? null)) {
            $data['vendor_id'] = static::getVendorId();
        }

        // Auto currency
        if (! ($data['currency_id']) && ($data['vendor_id'] ?? null)) {
            $vendor = static::resolveVendor($data['vendor_id']);
            $data['currency_id'] = $vendor?->currency_id;
        }

        return $data;
    }

    /* =============================================================
     * TABLE
     * ============================================================= */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference'),
                Tables\Columns\TextColumn::make('vendor.store_name'),
                Tables\Columns\TextColumn::make('supplier.name'),
                Tables\Columns\TextColumn::make('total_quantity'),
                Tables\Columns\TextColumn::make('total_cost')->money('USD'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Purchase $record) => $record->status === 'draft'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
