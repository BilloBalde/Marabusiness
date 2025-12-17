<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Filament\Resources\OrderResource\RelationManagers\AddressRelationManager;
use App\Models\Order;
use App\Models\Paiement;
use Filament\Forms;
use Filament\Forms\Set;
use Filament\Forms\Components\Group;
use App\Models\Product;
use App\Models\Vendor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\BelongsTo;
use App\Filament\Resources\Mask;
use App\Filament\Resources\OrderResource\RelationManagers\PaiementsRelationManager;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ShowAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Get;
use Filament\Support\Enums\NumberFormat;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\Number;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.orders');
    }

    public static function generateOrderNumber()
    {
        $currentYearMonth = now()->format('Ym'); // Get the current YearMonth (e.g., "202504")

        // Get the latest order number for the current year and month
        $latestOrder = DB::table('orders')
            ->where('order_number', 'like', "INV{$currentYearMonth}%")
            ->orderByDesc('order_number')
            ->first();

        // Get the latest increment number
        $increment = 1;
        if ($latestOrder) {
            $lastIncrement = (int)substr($latestOrder->order_number, -4); // Extract last 4 digits of the order number
            $increment = $lastIncrement + 1;
        }

        // Format the increment as a 4-digit number
        $formattedIncrement = str_pad($increment, 4, '0', STR_PAD_LEFT);

        // Return the full order number
        return "INV{$currentYearMonth}{$formattedIncrement}";
    }

    public static function generateTransactionNumber()
    {
        $currentYearMonth = now()->format('Ym'); // Get the current YearMonth (e.g., "202504")

        // Get the latest order number for the current year and month
        $latestPaiement = DB::table('paiements')
            ->where('transaction_id', 'like', "TRANS{$currentYearMonth}%")
            ->orderByDesc('transaction_id')
            ->first();

        // Get the latest increment number
        $increment = 1;
        if ($latestPaiement) {
            $lastIncrement = (int)substr($latestPaiement->transaction_id, -4); // Extract last 4 digits of the order number
            $increment = $lastIncrement + 1;
        }

        // Format the increment as a 4-digit number
        $formattedIncrement = str_pad($increment, 4, '0', STR_PAD_LEFT);

        // Return the full order number
        return "TRANS{$currentYearMonth}{$formattedIncrement}";
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Order Information')->schema([
                        Select::make('user_id')
                            ->label('Customer Info')
                            ->relationship('user', 'name')
                            ->preload()
                            ->searchable()
                            ->required(),
                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'store_name')
                            ->preload()
                            ->searchable()
                            ->required(),

                        Hidden::make('order_number')
                            ->default(function () {
                                return self::generateOrderNumber(); // Will generate the order number dynamically
                            }),

                        Hidden::make('transaction_id')
                            ->default(function () {
                                return self::generateTransactionNumber(); // Will generate the order number dynamically
                            }),

                        Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'stripe' => 'Stripe',
                                'paypal' => 'PayPal',
                                'cod' => 'Cash on Delivery',
                                'om' => 'Orange Money',
                            ])
                            ->required()
                            ->reactive(),

                        TextInput::make('amount')
                            ->label('Paid Amount')
                            ->numeric()
                            ->extraAttributes([
                                'x-data' => '{}',
                                'x-on:input' => "
                                    let val = \$el.value.replace(/,/g, '');
                                    if (!isNaN(val) && val !== '') {
                                        \$el.value = parseFloat(val).toLocaleString('en-US', {
                                            minimumFractionDigits: 0,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                ",
                                'inputmode' => 'decimal'
                            ])
                            ->default(0)
                            ->visible(fn(string $context) => $context === 'create') // ← show only in create
                            ->required(fn(string $context) => $context === 'create'), // ← required only in create,

                        FileUpload::make('image')
                            ->label('Payment Screenshot')
                            ->disk('public_uploads')
                            ->directory('payments')
                            ->visible(fn(string $context) => $context === 'create'), // ← show only in create

                        Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'partial' => 'Partial',
                                'paid' => 'Paid',
                                'failed' => 'Failed'
                            ])
                            ->required()
                            ->default('pending'),

                        ToggleButtons::make('status')
                            ->options([
                                'new' => 'New',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled'
                            ])
                            ->default('new')
                            ->inline()
                            ->required()
                            ->colors([
                                'new' => 'info',
                                'processing' => 'warning',
                                'shipped' => 'success',
                                'delivered' => 'success',
                                'cancelled' => 'danger'
                            ])
                            ->icons([
                                'new' => 'heroicon-m-sparkles',
                                'processing' => 'heroicon-m-arrow-path',
                                'shipped' => 'heroicon-m-truck',
                                'delivered' => 'heroicon-m-check-badge',
                                'cancelled' => 'heroicon-m-x-circle'
                            ]),

                        Select::make('shipping_carrier')
                            ->options([
                                'local' => 'Local Courier',
                                'chrono' => 'Chronopost',
                                'dhl' => 'DHL Express',
                                'ups' => 'UPS',
                                'fedex' => 'FedEx',
                                'other' => 'Other',
                            ])
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->label('Shipping Carrier')
                            ->helperText('Internal or third-party courier responsible for the delivery.'),
                        
                        // Add shipping amount field in your form
TextInput::make('shipping_amount')
    ->label('Shipping Cost')
    ->numeric()
    ->default(0)
    ->required()
    ->columnSpan(1),

// Then update all calculations to include shipping
Placeholder::make('grand_total_placeholder')
    ->label('Grand Total')
    ->content(function (Get $get, Set $set, $record) {
        $items = $get('items') ?? [];
        $itemsTotal = 0;
        
        foreach ($items as $item) {
            $itemsTotal += (float) ($item['total_amount'] ?? 0);
        }
        
        $shipping = (float) ($get('shipping_amount') ?? ($record->shipping_amount ?? 0));
        $grandTotal = $itemsTotal + $shipping;
        
        $set('grand_total', $grandTotal);
        $set('calculated_total', $grandTotal);
        
        // Get vendor currency symbol
        $vendorId = $record ? $record->vendor_id : $get('vendor_id');
        $vendor = Vendor::with('currency')->find($vendorId);
        $symbol = $vendor->currency->symbol ?? '$';
        
        return $symbol . number_format($grandTotal, 2);
    }),

Placeholder::make('total_paid_display')
    ->label('Total Paid')
    ->content(function (Get $get, $record) {
        if ($record) {
            // For edit/view: use stored total_paid from database
            $paid = $record->total_paid ?? 0;
        } else {
            // For create: use amount field
            $paid = (float) ($get('amount') ?? 0);
        }
        
        // Get vendor currency symbol
        $vendorId = $record ? $record->vendor_id : $get('vendor_id');
        $vendor = Vendor::with('currency')->find($vendorId);
        $symbol = $vendor->currency->symbol ?? '$';
        
        return $symbol . number_format($paid, 2);
    })->extraAttributes(['class' => 'text-green-600 font-bold']), // Add green color,

Placeholder::make('total_remaining_display')
    ->label('Balance Due')
    ->content(function (Get $get, $record) {
        if ($record) {
            // For edit/view: use stored total_remaining from database
            $remaining = $record->total_remaining ?? 0;
        } else {
            // For create: calculate from form
            $items = $get('items') ?? [];
            $itemsTotal = 0;
            foreach ($items as $item) {
                $itemsTotal += (float) ($item['total_amount'] ?? 0);
            }
            
            $shipping = (float) ($get('shipping_amount') ?? 0);
            $grandTotal = $itemsTotal + $shipping;
            
            $paid = (float) ($get('amount') ?? 0);
            $remaining = max($grandTotal - $paid, 0);
        }
        
        // Get vendor currency symbol
        $vendorId = $record ? $record->vendor_id : $get('vendor_id');
        $vendor = Vendor::with('currency')->find($vendorId);
        $symbol = $vendor->currency->symbol ?? '$';
        
        return $symbol . number_format($remaining, 2);
    })
    ->extraAttributes(function (Get $get, $record) {
        $colorClass = 'text-green-600'; // Default green for paid/zero balance
        
        if ($record) {
            if (($record->total_remaining ?? 0) > 0) {
                $colorClass = 'text-red-600'; // Red for balance due
            }
        } else {
            $items = $get('items') ?? [];
            $total = 0;
            foreach ($items as $item) {
                $total += (float) ($item['total_amount'] ?? 0);
            }
            
            $paid = (float) ($get('amount') ?? 0);
            $remaining = max($total - $paid, 0);
            
            if ($remaining > 0) {
                $colorClass = 'text-red-600'; // Red for balance due
            }
        }
        
        return ['class' => $colorClass . ' font-bold'];
    }),

                         /*    // Add this to your form schema
                        Hidden::make('calculated_total')
                            ->default(0)
                            ->reactive(),

                        Placeholder::make('total_paid_display')
                            ->label('Total Paid')
                            ->content(function (Get $get) {
                                $paid = (float) ($get('amount') ?? 0);
                                return '$' . number_format($paid, 2);
                            }),

                        Placeholder::make('total_remaining_display')
                            ->label('Total Remaining')
                            ->content(function (Get $get) {
                                $total = (float) ($get('calculated_total') ?? 0);
                                $paid = (float) ($get('amount') ?? 0);
                                $remaining = max($total - $paid, 0);
                                return '$' . number_format($remaining, 2);
                            }), */

                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ])->columns(2),
                    Section::make('Order Items')->schema([
                        Repeater::make('items') //this name is the relationship between the order items and the orders
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->options(function (Get $get) {
                                        $vendorId = $get('../../vendor_id');
                                        if (! $vendorId) return [];

                                        return \App\Models\VendorProduct::where('vendor_id', $vendorId)
                                            ->with('product')
                                            ->get()
                                            ->mapWithKeys(fn ($vp) => [
                                                $vp->product_id => $vp->product->name . ' — stock: ' . $vp->stock
                                            ]);
                                    })
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->columnSpan(4)
                                    ->reactive()
                                    ->searchable()
                                    ->required()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $vp = \App\Models\VendorProduct::where('product_id', $state)->first();
                                        $set('unit_amount', $vp?->price ?? 0);
                                        $set('total_amount', ($vp?->price ?? 0));
                                    }),

                                TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->columnSpan(2)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $unit = floatval($get('unit_amount'));
                                        $set('total_amount', $unit * $state);
                                    }),

                                TextInput::make('unit_amount')
                                    ->required()
                                    ->numeric()
                                    ->readOnly()    
                                    ->columnSpan(2),

                                TextInput::make('total_amount')
                                    ->required()
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan(2),
                            ])->columns(12)->defaultItems(0),

                        Placeholder::make('grand_total_placeholder')
                            ->label('Grand Total')
                            ->content(function (Get $get, Set $set) {
                                $total = 0;

                                $vendorCurrency = optional(
                                    Vendor::query()->with('currency')->find($get('vendor_id'))
                                )->currency->code ?? config('app.currency', 'USD');

                                $symbols = [
                                    'usd' => '$',
                                    'cad' => 'CA$',
                                    'gnf' => 'GNF',
                                    'cny' => '￥',
                                ];

                                $items = $get('items') ?? [];

                                foreach ($items as $key => $repeater) {
                                    $total += (float) ($repeater['total_amount'] ?? 0);
                                }

                                $set('grand_total', $total);

                                $symbol = $symbols[strtolower($vendorCurrency)] ?? $vendorCurrency;

                                return $symbol . number_format($total, 2);
                            }),
                        Hidden::make('grand_total')
                            ->default(0),
                    ]),
                ])->columnSpanFull()
            ]);
    }

    /**
 * Prepare order data before create/update.
 */
    public static function prepareOrderData(array $data, ?Order $record = null): array
{
    $items = [];
    $totalQty = 0;
    $itemsTotal = 0;

    foreach ($data['items'] ?? [] as $item) {
        $qty  = (int)($item['quantity'] ?? 0);
        $cost = (float)($item['unit_amount'] ?? 0);
        $line = round($qty * $cost, 2);

        $items[] = [
            'product_id'   => $item['product_id'],
            'quantity'     => $qty,
            'unit_amount'  => $cost,
            'total_amount' => $line,
        ];

        $totalQty  += $qty;
        $itemsTotal += $line;
    }

    // Save normalized items
    $data['items'] = $items;
    
    // Calculate grand total (items + shipping)
    $shippingAmount = (float) ($data['shipping_amount'] ?? 0);
    $data['grand_total'] = $itemsTotal + $shippingAmount;
    
    // Calculate payment totals
    $initialPayment = (float) ($data['amount'] ?? 0);
    $data['total_paid'] = $initialPayment;
    $data['total_remaining'] = max(0, $data['grand_total'] - $initialPayment);

    // Determine payment status
    if ($initialPayment <= 0) {
        $data['payment_status'] = 'pending';
    } elseif ($initialPayment >= $data['grand_total']) {
        $data['payment_status'] = 'paid';
    } else {
        $data['payment_status'] = 'partial';
    }

    // Determine currency from vendor
    if (!($data['currency_id'] ?? null)) {
        if (!empty($data['vendor_id'])) {
            $vendor = Vendor::with('currency')->find($data['vendor_id']);
            $data['currency_id'] = $vendor?->currency_id;
        }
    }

    return $data;
}


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->sortable()->searchable(),
                TextColumn::make('user.name')->sortable()->searchable()->label('Customer'),
                TextColumn::make('grand_total')->sortable()->numeric(),
                TextColumn::make('total_paid')->sortable()->numeric()->label('Total Paid'),
                TextColumn::make('total_remaining')->sortable()->numeric()->label('Total Remaining'),
                TextColumn::make('payment_method')->searchable()->sortable(),
                TextColumn::make('payment_status')->searchable()->sortable(),
                TextColumn::make('vendor.store_name')->label('Vendor')->searchable()->sortable(),
                TextColumn::make('shipping_carrier')->label('Shipping Carrier')->searchable()->sortable(),
                SelectColumn::make('status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled'
                    ])
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')->searchable()->sortable()->datetime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->searchable()->sortable()->datetime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('order_number'),
                SelectFilter::make('user_id')
                    ->relationship('user', 'name'),
                SelectFilter::make('vendor_id')
                    ->relationship('vendor', 'store_name'),
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'cod' => 'Cash on Delivery',
                        'om' => 'Orange Money',
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'failed' => 'Failed'
                    ]),
                SelectFilter::make('shipping_carrier')
                    ->options([
                        'local' => 'Local Courier',
                        'chrono' => 'Chronopost',
                        'dhl' => 'DHL Express',
                        'ups' => 'UPS',
                        'fedex' => 'FedEx',
                        'other' => 'Other',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled'
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
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
            AddressRelationManager::class,
            PaiementsRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 10 ? 'danger' : 'success';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
