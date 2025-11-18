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
        return "INV{$currentYearMonth}{$formattedIncrement}";
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

                        Select::make('currency')
                            ->options([
                                'cad' => 'CAD',
                                'usd' => 'USD',
                                'gnf' => 'GNF',
                            ])
                            ->required()
                            ->reactive()
                            ->default('gnf'),

                        Select::make('shipping_method')
                            ->options([
                                'fedex' => 'FEDEX',
                                'ups' => 'UPS',
                                'dhl' => 'DHL',
                                'other' => 'Other'
                            ])
                            ->default('fedex'),

                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ])->columns(2),
                    Section::make('Order Items')->schema([
                        Repeater::make('items') //this name is the relationship between the order items and the orders
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->columnSpan(4)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $price = Product::find($state)?->price ?? 0;
                                        $set('unit_amount', $price);
                                        $set('total_amount', $price);
                                    }),

                                TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->columnSpan(2)
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, Set $set, Get $get) => $set('total_amount', $state * $get('unit_amount'))),

                                TextInput::make('unit_amount')
                                    ->required()
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),

                                TextInput::make('total_amount')
                                    ->required()
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),
                            ])->columns(12),

                        Placeholder::make('grand_total_placeholder')
                            ->label('Grand Total')
                            ->content(function (Get $get, Set $set) {
                                $total = 0;
                                // Get the currency selected
                                $currency = $get('currency') ?? 'usd';

                                // Currency symbol mapping
                                $symbols = [
                                    'usd' => '$',
                                    'cad' => 'CA$',
                                    'gnf' => 'gnf',
                                ];

                                $symbol = $symbols[$currency] ?? '$';

                                if (!$repeaters = $get('items')) {
                                    return $total;
                                }

                                foreach ($repeaters as $key => $repeater) {
                                    $total += $get("items.{$key}.total_amount");
                                }

                                $set('grand_total', $total);
                                return $symbol . number_format($total, 2);
                            }),
                        Hidden::make('grand_total')
                            ->default(0),
                    ]),
                ])->columnSpanFull()
            ]);
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
                TextColumn::make('currency')->searchable()->sortable(),
                TextColumn::make('shipping_method')->searchable()->sortable(),
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
                SelectFilter::make('payment_method')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed'
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed'
                    ]),
                SelectFilter::make('currency')
                    ->options([
                        'cad' => 'CAD',
                        'usd' => 'USD',
                        'eur' => 'EUR'
                    ]),
                SelectFilter::make('shipping_method')
                    ->options([
                        'fedex' => 'FEDEX',
                        'ups' => 'UPS',
                        'dhl' => 'DHL',
                        'other' => 'Other'
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
