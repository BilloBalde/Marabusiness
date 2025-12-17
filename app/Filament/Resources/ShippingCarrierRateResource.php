<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingCarrierRateResource\Pages;
use App\Models\ShippingCarrierRate;
use App\Models\ShippingZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShippingCarrierRateResource extends Resource
{
    protected static ?string $model = ShippingCarrierRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?string $navigationLabel = 'Carrier Rates';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'store_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->label('Vendor')
                            ->afterStateUpdated(function (callable $set) {
                                $set('zone_name', null);
                            }),

                        Forms\Components\Select::make('zone_name')
                            ->label('Shipping Zone')
                            ->options(function (callable $get) {
                                $vendorId = $get('vendor_id');
                                if (!$vendorId) {
                                    return [];
                                }
                                return ShippingZone::where('vendor_id', $vendorId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'name');
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Load zone details when zone is selected
                                if ($state) {
                                    $zone = ShippingZone::where('name', $state)->first();
                                    if ($zone) {
                                        // You could auto-fill some values here if needed
                                    }
                                }
                            }),

                        Forms\Components\Select::make('carrier')
                            ->options(ShippingCarrierRate::CARRIERS)
                            ->searchable()
                            ->required()
                            ->label('Carrier'),

                        Forms\Components\TextInput::make('service_code')
                            ->label('Service Code')
                            ->maxLength(100)
                            ->placeholder('e.g., EXPRESS, STANDARD, ECONOMY')
                            ->helperText('Carrier-specific service code'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Carrier')
                            ->helperText('Mark as default carrier for this zone')
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rate Calculation')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('base_rate')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->label('Base Rate')
                                    ->helperText('Fixed base rate'),

                                Forms\Components\TextInput::make('rate_per_kg')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->label('Rate per kg'),

                                Forms\Components\TextInput::make('rate_per_cbm')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->label('Rate per CBM'),

                                Forms\Components\TextInput::make('rate_per_item')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->label('Rate per item'),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('rate_per_carton')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->label('Rate per carton'),

                                Forms\Components\TextInput::make('min_rate')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->label('Minimum Rate')
                                    ->helperText('Minimum charge for this carrier'),

                                Forms\Components\TextInput::make('max_rate')
                                    ->numeric()
                                    ->prefix('$')
                                    ->label('Maximum Rate')
                                    ->helperText('Maximum charge (optional)'),
                            ]),
                    ]),

                Forms\Components\Section::make('Delivery & Conditions')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('delivery_days')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(3)
                                    ->label('Delivery Days')
                                    ->helperText('Estimated delivery in days'),

                                Forms\Components\TextInput::make('free_shipping_threshold')
                                    ->numeric()
                                    ->prefix('$')
                                    ->label('Free Shipping Threshold')
                                    ->helperText('Order amount for free shipping'),

                                Forms\Components\TextInput::make('carrier_account_id')
                                    ->label('Carrier Account ID')
                                    ->maxLength(100)
                                    ->placeholder('e.g., ACCT_123456')
                                    ->helperText('Carrier-specific account identifier'),
                            ]),
                    ]),

                Forms\Components\Section::make('Examples')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Placeholder::make('example_calculation')
                            ->label('Example Calculation')
                            ->content(function (callable $get) {
                                $weight = 5; // kg
                                $cbm = 0.1; // cubic meters
                                $items = 3;
                                $cartons = 1;

                                $base = $get('base_rate') ?? 0;
                                $perKg = $get('rate_per_kg') ?? 0;
                                $perCbm = $get('rate_per_cbm') ?? 0;
                                $perItem = $get('rate_per_item') ?? 0;
                                $perCarton = $get('rate_per_carton') ?? 0;

                                $total = $base + 
                                        ($perKg * $weight) + 
                                        ($perCbm * $cbm) + 
                                        ($perItem * $items) + 
                                        ($perCarton * $cartons);

                                $min = $get('min_rate') ?? 0;
                                $max = $get('max_rate');

                                if ($min > 0 && $total < $min) {
                                    $total = $min;
                                }
                                if ($max > 0 && $total > $max) {
                                    $total = $max;
                                }

                                return "Example for 5kg, 0.1m³, 3 items, 1 carton: $" . number_format($total, 2);
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.store_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('zone_name')
                    ->label('Zone')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('carrier_name')
                    ->label('Carrier')
                    ->formatStateUsing(fn ($record) => $record->carrier_name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('service_code')
                    ->label('Service Code')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('base_rate')
                    ->label('Base Rate')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rate_per_kg')
                    ->label('Rate/kg')
                    ->money('USD')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delivery_days')
                    ->label('Days')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state . ' days'),

                Tables\Columns\TextColumn::make('estimated_delivery')
                    ->label('Delivery')
                    ->getStateUsing(fn ($record) => $record->estimated_delivery)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('free_shipping_threshold')
                    ->label('Free Ship >')
                    ->money('USD')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor')
                    ->relationship('vendor', 'store_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('zone_name')
                    ->options(fn () => ShippingCarrierRate::distinct('zone_name')->pluck('zone_name', 'zone_name')->sort())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('carrier')
                    ->options(ShippingCarrierRate::CARRIERS)
                    ->searchable(),

                Tables\Filters\Filter::make('is_active')
                    ->label('Active Rates')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),

                Tables\Filters\Filter::make('is_default')
                    ->label('Default Carriers')
                    ->query(fn (Builder $query): Builder => $query->where('is_default', true)),

                Tables\Filters\Filter::make('has_free_shipping')
                    ->label('Has Free Shipping')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('free_shipping_threshold')->where('free_shipping_threshold', '>', 0)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('calculate')
                    ->label('Calculate')
                    ->icon('heroicon-o-calculator')
                    ->form([
                        Forms\Components\TextInput::make('weight')
                            ->label('Weight (kg)')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        Forms\Components\TextInput::make('cbm')
                            ->label('Volume (CBM)')
                            ->numeric()
                            ->default(0.01),
                        Forms\Components\TextInput::make('items')
                            ->label('Items')
                            ->numeric()
                            ->default(1),
                        Forms\Components\TextInput::make('cartons')
                            ->label('Cartons')
                            ->numeric()
                            ->default(0),
                    ])
                    ->action(function ($record, array $data) {
                        $cost = $record->calculateCost(
                            $data['weight'],
                            $data['cbm'],
                            $data['items'],
                            $data['cartons']
                        );
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Shipping Cost Calculation')
                            ->body("Estimated cost: $" . number_format($cost, 2))
                            ->info()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('setDefault')
                        ->label('Set as Default')
                        ->icon('heroicon-o-star')
                        ->action(function ($records) {
                            // First, clear defaults for the same vendor and zone
                            foreach ($records as $record) {
                                ShippingCarrierRate::where('vendor_id', $record->vendor_id)
                                    ->where('zone_name', $record->zone_name)
                                    ->where('id', '!=', $record->id)
                                    ->update(['is_default' => false]);
                            }
                            
                            // Then set selected as default
                            $records->each->update(['is_default' => true]);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('vendor_id')
            ->groups([
                Tables\Grouping\Group::make('vendor.store_name')
                    ->label('Vendor')
                    ->collapsible(),
                
                Tables\Grouping\Group::make('zone_name')
                    ->label('Zone')
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingCarrierRates::route('/'),
            'create' => Pages\CreateShippingCarrierRate::route('/create'),
            'edit' => Pages\EditShippingCarrierRate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('vendor')
            ->orderBy('vendor_id')
            ->orderBy('zone_name')
            ->orderBy('carrier');
    }

    public static function getNavigationItems(): array
    {
        return [
            \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.*'))
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->url(static::getNavigationUrl()),
        ];
    }
}