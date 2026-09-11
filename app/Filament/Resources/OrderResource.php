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

    /**
     * Was a third copy of the same generator. Three copies meant the one fixed bug —
     * the model's returning "INV" while searching "TRANS" — could sit undetected next
     * to two correct versions.
     */
    public static function generateTransactionNumber(): string
    {
        return Order::generateTransactionNumber();
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
                                'cod' => 'Cash on Delivery',
                                'stripe' => 'Stripe',
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

                        // Same gap as the table's status column: without a
                        // 'negotiating' option, editing an order under negotiation
                        // opened this required field with nothing selected and
                        // forced whoever saved to pick another status — ending the
                        // discussion by accident and making the order payable at a
                        // price nobody had agreed. Disabled while negotiating, so
                        // Filament leaves the column alone (a disabled field is not
                        // dehydrated); the way out is the price / accept / cancel
                        // path, which writes the agreed total and its entries.
                        ToggleButtons::make('status')
                            ->options([
                                Order::STATUS_NEGOTIATING => 'Negotiating',
                                'new' => 'New',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled'
                            ])
                            ->default('new')
                            ->inline()
                            ->required()
                            ->disabled(fn (?Order $record): bool => $record?->status === Order::STATUS_NEGOTIATING)
                            ->helperText(fn (?Order $record): ?string => $record?->status === Order::STATUS_NEGOTIATING
                                ? "Commande en négociation : le statut se règle en fixant le prix ou en annulant."
                                : null)
                            ->colors([
                                Order::STATUS_NEGOTIATING => 'warning',
                                'new' => 'info',
                                'processing' => 'warning',
                                'shipped' => 'success',
                                'delivered' => 'success',
                                'cancelled' => 'danger'
                            ])
                            ->icons([
                                Order::STATUS_NEGOTIATING => 'heroicon-m-chat-bubble-left-right',
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
                                'msk' => 'MSK',
                                'cma' => 'CMA CGM',
                                'msc' => 'MSC',
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
                                $set('calculated_total', $grandTotal); // new fix
                                
                                // Get vendor currency symbol
                                $vendorId = $record ? $record->vendor_id : $get('vendor_id');
                                $vendor = Vendor::with('currency')->find($vendorId);
                                $symbol = $vendor->currency->symbol ?? '$';
                                
                                return $symbol . number_format($grandTotal, 2);
                            }),

                        Placeholder::make('total_paid_display')
                            ->label('Total Paid')
                            ->live()
                            ->content(function (Get $get, $record) {
                                if ($record) {
                                    // For edit/view: use stored total_paid from database
                                    //$paid = $record->total_paid ?? 0;
                                    // Money the shop has actually received — not what a
                                    // buyer has merely declared and nobody confirmed yet.
                                    $paid = $record->confirmedPaiements()->sum('amount') ?? 0;
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
                            ->live()
                            ->content(function (Get $get, $record) {
                                // Calculate items total
                                $items = $get('items') ?? [];
                                $itemsTotal = 0;
                                foreach ($items as $item) {
                                    $itemsTotal += (float) ($item['total_amount'] ?? 0);
                                }
                                
                                // Add shipping
                                $shipping = (float) ($get('shipping_amount') ?? ($record->shipping_amount ?? 0));
                                $grandTotal = $itemsTotal + $shipping;
                                if ($record) {
                                    // For edit/view: use actual payments
                                    // Money the shop has actually received — not what a
                                    // buyer has merely declared and nobody confirmed yet.
                                    $paid = $record->confirmedPaiements()->sum('amount') ?? 0;
                                } else {
                                    // For create: use amount field
                                    $paid = (float) ($get('amount') ?? 0);
                                }
                                
                                $remaining = max(0, $grandTotal - $paid);
                                
                                // Get vendor currency symbol
                                $vendorId = $record ? $record->vendor_id : $get('vendor_id');
                                $vendor = Vendor::with('currency')->find($vendorId);
                                $symbol = $vendor->currency->symbol ?? '$';
                                
                                return $symbol . number_format($remaining, 2);
                            })
                            ->extraAttributes(function (Get $get, $record) {
                                // Recalculate to get remaining
                                $items = $get('items') ?? [];
                                $itemsTotal = 0;
                                foreach ($items as $item) {
                                    $itemsTotal += (float) ($item['total_amount'] ?? 0);
                                }
                                
                                $shipping = (float) ($get('shipping_amount') ?? ($record->shipping_amount ?? 0));
                                $grandTotal = $itemsTotal + $shipping;
                                
                                if ($record) {
                                    // Money the shop has actually received — not what a
                                    // buyer has merely declared and nobody confirmed yet.
                                    $paid = $record->confirmedPaiements()->sum('amount') ?? 0;
                                } else {
                                    $paid = (float) ($get('amount') ?? 0);
                                }
                                
                                $remaining = max(0, $grandTotal - $paid);
                                
                                $colorClass = $remaining > 0 ? 'text-red-600' : 'text-green-600';
                                return ['class' => $colorClass . ' font-bold'];
                            }),


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
                                    ->columnSpan([
                                        'default' => 12,
                                        'sm' => 12,
                                        'md' => 5,
                                        'lg' => 6,
                                        'xl' => 3,
                                    ])
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
                                    ->columnSpan([
                                        'default' => 6,
                                        'sm' => 6,
                                        'md' => 2,
                                        'lg' => 2,
                                        'xl' => 1,
                                    ])
                                    ->reactive()
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $unit = floatval($get('unit_amount'));
                                        $set('total_amount', $unit * $state);
                                    }),

                                TextInput::make('unit_amount')
                                    ->required()
                                    ->numeric()  
                                    ->columnSpan([
                                    'default' => 6,
                                    'sm' => 6,
                                    'md' => 2,
                                    'lg' => 2,
                                    'xl' => 2,
                                ])
                                    ->reactive()
                                    ->debounce(500)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $quantity = floatval($get('quantity'));
                                        $set('total_amount', $state * $quantity);
                                    }),

                                TextInput::make('total_amount')
                                    ->required()
                                    ->numeric()
                                    ->readOnly()
                                    ->columnSpan([
                                        'default' => 12,
                                        'sm' => 12,
                                        'md' => 3,
                                        'lg' => 2,
                                        'xl' => 2,
                                    ]),
                                Textarea::make('variation_json')
                                    ->label('Variations')
                                    ->columnSpan([
                                        'default' => 12,
                                        'sm' => 12,
                                        'md' => 5,
                                        'lg' => 4,
                                        'xl' => 4,
                                    ])
                                    ->helperText('Enter variations as JSON or key-value pairs')
                                    ->formatStateUsing(function ($state) {
                                        // If state is already an array (from cast), convert to JSON string for display
                                        if (is_array($state)) {
                                            return json_encode($state, JSON_PRETTY_PRINT);
                                        }
                                        // If it's a JSON string, format it nicely
                                        if (is_string($state) && json_decode($state)) {
                                            $decoded = json_decode($state, true);
                                            return json_encode($decoded, JSON_PRETTY_PRINT);
                                        }
                                        return $state;
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        // Convert the input to a proper JSON string
                                        if (empty($state)) {
                                            return null;
                                        }
                                        
                                        // If it's already a JSON string, decode and re-encode to ensure validity
                                        if (is_string($state) && json_decode($state)) {
                                            $decoded = json_decode($state, true);
                                            return json_encode($decoded);
                                        }
                                        
                                        // Try to parse as JSON if it looks like JSON
                                        $trimmed = trim($state);
                                        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                                            $decoded = json_decode($state, true);
                                            if (json_last_error() === JSON_ERROR_NONE) {
                                                return json_encode($decoded);
                                            }
                                        }
                                        
                                        // Try to parse as key-value pairs (e.g., "color: red, size: large")
                                        $pairs = explode(',', $state);
                                        $variations = [];
                                        foreach ($pairs as $pair) {
                                            $parts = explode(':', $pair, 2);
                                            if (count($parts) === 2) {
                                                $key = trim($parts[0]);
                                                $value = trim($parts[1]);
                                                if (!empty($key)) {
                                                    $variations[$key] = $value;
                                                }
                                            }
                                        }
                                        
                                        return !empty($variations) ? json_encode($variations) : null;
                                    })
                                    ->disabled(fn($context) => $context === 'view') // Disable in view mode
                                    ->readOnly(fn($context) => $context === 'view')
                                    ->hint(function ($state) {
                                        // Display a user-friendly hint about the variations
                                        if (empty($state)) {
                                            return 'No variations';
                                        }
                                        
                                        try {
                                            $variations = json_decode($state, true);
                                            if ($variations && is_array($variations)) {
                                                $formatted = [];
                                                foreach ($variations as $key => $value) {
                                                    $formatted[] = ucfirst($key) . ': ' . $value;
                                                }
                                                return implode(', ', $formatted);
                                            }
                                        } catch (\Exception $e) {
                                            // If not valid JSON, return as-is
                                        }
                                        
                                        return $state;
                                    }),
                            ])->columns(12)
                            ->defaultItems(0)
                            ->itemLabel(function (array $state): ?string {
                                // Create a better label for each item showing product and variations
                                if (empty($state['product_id'])) {
                                    return 'New Item';
                                }
                                
                                $product = \App\Models\Product::find($state['product_id']);
                                $productName = $product->name ?? 'Product #' . $state['product_id'];
                                
                                // Add variations to label if present
                                $variationsText = '';
                                if (!empty($state['variation_json'])) {
                                    try {
                                        $variations = json_decode($state['variation_json'], true);
                                        if ($variations && is_array($variations)) {
                                            $variationParts = [];
                                            foreach ($variations as $key => $value) {
                                                $variationParts[] = ucfirst($key) . ': ' . $value;
                                            }
                                            $variationsText = ' (' . implode(', ', $variationParts) . ')';
                                        }
                                    } catch (\Exception $e) {
                                        // Ignore JSON errors
                                    }
                                }
                                
                                return $productName . $variationsText . ' × ' . ($state['quantity'] ?? 1);
                            }),
                        Placeholder::make('items_subtotal')
                            ->label('Items Subtotal')
                            ->live()
                            ->content(function (Get $get) {
                                $total = 0;
                                $items = $get('items') ?? [];
                                foreach ($items as $item) {
                                    $total += (float) ($item['total_amount'] ?? 0);
                                }
                                return '$' . number_format($total, 2);
                            }),

                        Placeholder::make('shipping_cost_display')
                            ->label('Shipping Cost')
                            ->live()
                            ->content(function (Get $get, $record) {
                                $shipping = (float) ($get('shipping_amount') ?? ($record->shipping_amount ?? 0));
                                return '$' . number_format($shipping, 2);
                            }),

                        Placeholder::make('grand_total_placeholder')
                            ->label('Grand Total')
                            ->live()
                            ->content(function (Get $get, $record) {
                                // Calculate items total
                                $items = $get('items') ?? [];
                                $itemsTotal = 0;
                                $vendorCurrency = optional(
                                    Vendor::query()->with('currency')->find($get('vendor_id'))
                                )->currency->code ?? config('app.currency', 'USD');
                                foreach ($items as $item) {
                                    $itemsTotal += (float) ($item['total_amount'] ?? 0);
                                }
                                
                                // Add shipping
                                $shipping = (float) ($get('shipping_amount') ?? ($record->shipping_amount ?? 0));
                                $grandTotal = $itemsTotal + $shipping;
                                $symbols = [
                                    'usd' => '$',
                                    'cad' => 'CA$',
                                    'gnf' => 'GNF',
                                    'cny' => '￥',
                                ];
                                $symbol = $symbols[strtolower($vendorCurrency)] ?? $vendorCurrency;

                                return $symbol . number_format($grandTotal, 2);
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
                'variation_json' => $item['variation_json'] ?? null,
            ];

            $totalQty  += $qty;
            $itemsTotal += $line;
        }

        // Save normalized items
        $data['items'] = $items;
        
        // Calculate grand total (items + shipping)
        $shippingAmount = (float) ($data['shipping_amount'] ?? ($record->shipping_amount ?? 0));
        $data['grand_total'] = $itemsTotal + $shippingAmount;
        
        /* // Calculate payment totals
        $initialPayment = (float) ($data['amount'] ?? ($record->total_paid ?? 0));
        
        // For edit mode, preserve existing payments
        if ($record) {
            $totalPaid = $record->paiements()->sum('amount');
            $data['total_paid'] = $totalPaid;
            $data['total_remaining'] = max(0, $data['grand_total'] - $totalPaid);
            
            // Determine payment status based on existing payments
            if ($totalPaid >= $data['grand_total']) {
                $data['payment_status'] = 'paid';
            } elseif ($totalPaid > 0) {
                $data['payment_status'] = 'partial';
            } else {
                $data['payment_status'] = 'pending';
            }
        } else {
            // For create mode
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
        } */

        if (!$record) {
            // For create mode only
            $initialPayment = (float) str_replace(',', '', (string) ($data['amount'] ?? 0));
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
        }

        // Determine currency from vendor
        if (!($data['currency_id'] ?? null)) {
            $vendorId = $data['vendor_id'] ?? ($record->vendor_id ?? null);
            if ($vendorId) {
                $vendor = Vendor::with('currency')->find($vendorId);
                $data['currency_id'] = $vendor?->currency_id;
            }
        }

        return $data;
    }


    public static function table(Table $table): Table
    {
        return $table
            // Aucun tri par défaut n'était posé : la table sortait dans l'ordre
            // naturel de la base, c'est-à-dire par id croissant. La commande la
            // plus récente — celle qui attend quelque chose — se retrouvait donc
            // sur la DERNIÈRE page. Une boutique avec cinquante commandes ne
            // pouvait pas trouver la négociation que sa pastille lui signalait.
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')->sortable()->searchable(),
                TextColumn::make('user.name')->sortable()->searchable()->label('Customer'),
                TextColumn::make('grand_total')->sortable()->numeric(),
                TextColumn::make('total_paid')->sortable()->numeric()->label('Total Paid'),
                TextColumn::make('total_remaining')->sortable()->numeric()->label('Total Remaining'),
                TextColumn::make('vendor.currency.code')->label('Currency')->sortable()->searchable(),
                TextColumn::make('payment_status')->searchable()->sortable(),
                TextColumn::make('vendor.store_name')->label('Vendor')->searchable()->sortable(),
                TextColumn::make('shipping_carrier')->label('Shipping Carrier')->searchable()->sortable(),
                // 'negotiating' was absent from these options, so an order under
                // negotiation showed an empty dropdown here — and anyone could pick
                // "New" from it, making an order payable at its catalogue price
                // while the vendor had not yet named one and the buyer had agreed
                // to nothing. The status is listed so the row reads correctly, and
                // the control is locked while the discussion is open: leaving a
                // negotiation goes through the price/accept/cancel path, which
                // writes the agreed total and the financial entries with it.
                SelectColumn::make('status')
                    ->options([
                        Order::STATUS_NEGOTIATING => 'Negotiating',
                        'new' => 'New',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled'
                    ])
                    ->disabled(fn (Order $record): bool => $record->status === Order::STATUS_NEGOTIATING)
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
                        'cod' => 'Cash on Delivery',
                        'stripe' => 'Stripe',
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
                        'msk' => 'MSK',
                        'cma' => 'CMA CGM',
                        'msc' => 'MSC',
                        'other' => 'Other',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        Order::STATUS_NEGOTIATING => 'Negotiating',
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
            //'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
