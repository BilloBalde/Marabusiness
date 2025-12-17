<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Brand;
use App\Models\Category;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon  = 'heroicon-o-shopping-cart';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.purchases');
    }

    /** ============================================================
     *  PANEL CONTEXT HELPERS
     *  ============================================================ */

    protected static function isVendorPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'vendor';
    }

    protected static function getVendorId(): ?int
    {
        $user = Filament::auth()->user();

        if (! $user) return null;

        if ($user instanceof \App\Models\Vendor) {
            return $user->id;
        }

        // If user has a vendor() relation
        if (method_exists($user, 'vendor') && $user->vendor) {
            return $user->vendor->id;
        }

        // If user has vendor_id column
        return $user->vendor_id ?? null;
    }

    protected static function resolveVendor(?int $vendorId): ?Vendor
    {
        if (! $vendorId) return null;
        return Vendor::with('currency')->find($vendorId);
    }

    /** ============================================================
     *  FORM
     * ============================================================ */

    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make("Purchase Info")
                ->schema([

                    TextInput::make('reference')
                        ->default(fn () => Purchase::generateReference())
                        ->readOnly()
                        ->dehydrated(),

                    // ADMIN: choose vendor
                    // VENDOR PANEL: auto-set vendor, hidden
                    Select::make('vendor_id')
                        ->label('Vendor')
                        ->hidden(fn () => static::isVendorPanel())
                        ->default(fn () => static::getVendorId())
                        ->disabled(fn () => static::isVendorPanel())
                        ->options(Vendor::orderBy('store_name')->pluck('store_name', 'id'))
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            $currencyId = Vendor::where('id', $state)->value('currency_id');
                            $set('currency_id', $currencyId);
                            $set('items', []);
                            $set('total_cost', 0);
                            $set('total_quantity', 0);
                        }),

                    Select::make('supplier_id')
                        ->relationship('supplier', 'name')
                        ->preload()
                        ->searchable()
                        ->required(),

                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'ordered' => 'Ordered',
                            'received' => 'Received',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('draft'),

                    Hidden::make('currency_id')
                        ->dehydrated()
                        ->default(fn (Get $get) =>
                            static::resolveVendor($get('vendor_id'))?->currency_id
                        ),

                ])->columns(2),

            /* ============================================================
             * ITEMS
             * ============================================================ */
            Section::make("Items")
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->columns(4)
                        ->live()
                        ->afterStateHydrated(fn ($state, Set $set) =>
                            static::recalculateTotals($state ?? [], $set)
                        )
                        ->afterStateUpdated(fn ($state, Set $set) =>
                            static::recalculateTotals($state ?? [], $set)
                        )
                        ->schema([

                            Select::make('vendor_product_id')
                                ->label('Product')
                                ->required()
                                ->options(function (Get $get) {
                                    $vendorId = $get('../../vendor_id');
                                    if (! $vendorId) return [];

                                    return VendorProduct::where('vendor_id', $vendorId)
                                        ->with('product')
                                        ->get()
                                        ->mapWithKeys(fn ($vp) => [
                                            $vp->id =>
                                                ($vp->product->name ?? "Product") .
                                                " — " . number_format($vp->price, 2)
                                        ]);
                                })
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $vp = VendorProduct::with('product')->find($state);
                                    if (!$vp) return;

                                    $set('product_id', $vp->product_id);
                                    $set('unit_cost', $vp->price);

                                    $qty = (int) $get('quantity') ?: 1;
                                    $set('total_cost_items', $qty * $vp->price);
                                })
                                ->createOptionForm([
                                    TextInput::make('name')->required(),
                                    Select::make('category_id')->options(
                                        Category::pluck('name', 'id')
                                    )->required(),
                                    Select::make('brand_id')->options(
                                        Brand::pluck('name', 'id')
                                    )->required(),
                                    TextInput::make('price')->numeric()->required()->label('Base Price'),
                                    // ==========================
                                    // 🔥 ON SALE TOGGLE
                                    // ==========================
                                    Radio::make('on_sale')
                                        ->label('Discount / Promotion')
                                        ->options([
                                            false => 'No promotion',
                                            true  => 'Activate Promotion',
                                        ])
                                        ->default(false)
                                        ->inline()
                                        ->reactive(),

                                    // ==========================
                                    // 🔥 SALE PRICE
                                    // ==========================
                                    TextInput::make('sale_price')
                                        ->label('Sale Price')
                                        ->numeric()
                                        ->visible(fn ($get) => (bool)$get('on_sale') === true)
                                        ->helperText('Must be lower than base price'),

                                    // ==========================
                                    // 🔥 START / END DATE
                                    // ==========================
                                    DatePicker::make('sale_start')
                                        ->visible(fn ($get) => (bool)$get('on_sale') === true)
                                        ->label('Sale Starts'),

                                    DatePicker::make('sale_end')
                                        ->visible(fn ($get) => (bool)$get('on_sale') === true)
                                        ->label('Sale Ends'),
                                ])
                                ->createOptionUsing(function (array $data, Get $get, Set $set) {
                                    $vendorId = $get('../../vendor_id');

                                    if (!$vendorId) {
                                        throw ValidationException::withMessages([
                                            'vendor_id' => "Select a vendor first."
                                        ]);
                                    }
                                    // Normalize on_sale (can come as true/false, "1"/"0", etc.)
                                    $onSale = !empty($data['on_sale']) && $data['on_sale'];

                                    $product = Product::create([
                                        'name' => $data['name'],
                                        'slug' => Str::slug($data['name']) . "-" . Str::random(4),
                                        'category_id' => $data['category_id'],
                                        'brand_id' => $data['brand_id'],
                                        'price' => $data['price'],
                                        'on_sale' => $onSale,
                                    ]);

                                     // 2️⃣ Calculate discount if sale_price is provided
                                    $salePrice = $onSale ? ($data['sale_price'] ?? null) : null;
                                    $discount = null;

                                    if (!empty($data['sale_price']) && $data['sale_price'] < $data['price']) {
                                        $discount = round(100 - ($data['sale_price'] / $data['price'] * 100));
                                    }

                                    // 3️⃣ Create the VendorProduct
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
                                    $set('vendor_product_id', $vp->id);   // SELECT THE NEW OPTION
                                    $set('product_id', $product->id);     // hydrate product ID
                                    $set('unit_cost', $data['price']);    // update unit cost
                                    return $vp->id;
                                }),

                            Hidden::make('product_id')->dehydrated(),

                            TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->reactive()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $qty = (int) $get('quantity');
                                    $set('total_cost_items', $qty * (float)$get('unit_cost'));
                                }),

                            TextInput::make('unit_cost')
                                ->numeric()
                                ->reactive()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $cost = (float) $get('unit_cost');
                                    $set('total_cost_items', $cost * (int)$get('quantity'));
                                }),

                            TextInput::make('total_cost_items')->readOnly(),

                        ]),
                ]),

            /* ============================================================
             * TOTALS
             * ============================================================ */
            Section::make("Totals")
                ->schema([
                    TextInput::make('total_quantity')
                        ->readOnly()
                        ->dehydrated(),

                    TextInput::make('total_cost')
                        ->readOnly()
                        ->dehydrated(),
                ])->columns(2),
        ]);
    }

    /** ============================================================
     * TOTALS
     * ============================================================ */

    protected static function recalculateTotals(array $items, Set $set): void
    {
        $totalQty = 0;
        $totalCost = 0;

        foreach ($items as $item) {
            $totalQty += (int) ($item['quantity'] ?? 0);
            $totalCost += (float) ($item['total_cost_items'] ?? 0);
        }

        $set("total_quantity", $totalQty);
        $set("total_cost", $totalCost);
    }


    /** ============================================================
     * NORMALIZE ITEM DATA
     * ============================================================ */

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

    /** ============================================================
     * TABLE
     * ============================================================ */

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
            'index'  => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit'   => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
