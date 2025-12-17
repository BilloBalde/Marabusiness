<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingZoneResource\Pages;
use App\Models\ShippingZone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShippingZoneResource extends Resource
{
    protected static ?string $model = ShippingZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?string $navigationLabel = 'Shipping Zones';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Zone Information')
                    ->schema([
                        Forms\Components\Select::make('vendor_id')
                            ->relationship('vendor', 'store_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Vendor')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Zone Name')
                            ->placeholder('e.g., Zone A, Local, International'),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->rows(3)
                            ->label('Description')
                            ->placeholder('Describe this shipping zone'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Geographic Coverage')
                    ->schema([
                        Forms\Components\TextInput::make('country_code')
                            ->label('Country Code')
                            ->maxLength(3)
                            ->placeholder('e.g., GN, US, FR')
                            ->helperText('ISO 3166-1 alpha-2 country code'),

                        Forms\Components\TextInput::make('region')
                            ->label('Region/State')
                            ->maxLength(255)
                            ->placeholder('e.g., Conakry, California'),

                        Forms\Components\TagsInput::make('cities')
                            ->label('Cities')
                            ->placeholder('Add cities')
                            ->helperText('Cities included in this zone. Leave empty for all cities.')
                            ->separator(','),

                        Forms\Components\TextInput::make('radius_km')
                            ->label('Radius (km)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.1)
                            ->suffix('km')
                            ->placeholder('e.g., 50 for 50km radius')
                            ->helperText('Delivery radius from vendor location'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing & Delivery')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('base_price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->label('Base Price'),

                                Forms\Components\TextInput::make('price_per_kg')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->label('Price per kg'),

                                Forms\Components\TextInput::make('price_per_cbm')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->label('Price per CBM'),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('price_per_item')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->label('Price per item'),

                                Forms\Components\TextInput::make('price_per_carton')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->label('Price per carton'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_days')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->label('Minimum Delivery Days'),

                                Forms\Components\TextInput::make('max_days')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(7)
                                    ->label('Maximum Delivery Days'),
                            ]),
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

                Tables\Columns\TextColumn::make('name')
                    ->label('Zone Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Full Zone Name')
                    ->description(fn ($record) => $record->description)
                    ->searchable(['name', 'country_code', 'region'])
                    ->wrap(),

                Tables\Columns\TextColumn::make('country_code')
                    ->label('Country')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('region')
                    ->label('Region')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cities_count')
                    ->label('Cities')
                    ->getStateUsing(function ($record) {
                        try {
                            $cities = $record->cities;
                            
                            if (is_string($cities)) {
                                if (empty(trim($cities))) {
                                    return 0;
                                }
                                return count(explode(',', $cities));
                            }
                            
                            return count((array) $cities);
                        } catch (\Exception $e) {
                            return 0;
                        }
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('base_price')
                    ->label('Base Price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('delivery_time')
                    ->label('Delivery Time')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor')
                    ->relationship('vendor', 'store_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('is_active')
                    ->label('Active Zones')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),

                Tables\Filters\Filter::make('has_country')
                    ->label('Has Country')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('country_code')),

                Tables\Filters\SelectFilter::make('country_code')
                    ->options(fn () => ShippingZone::distinct('country_code')->pluck('country_code', 'country_code')->filter()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('viewRates')
                    ->label('Rates')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn ($record) => ShippingCarrierRateResource::getUrl('index', [
                        'tableFilters' => [
                            'zone_name' => ['value' => $record->name],
                            'vendor' => ['value' => $record->vendor_id],
                        ]
                    ])),
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
                ]),
            ])
            ->defaultSort('vendor_id')
            ->groups([
                Tables\Grouping\Group::make('vendor.store_name')
                    ->label('Vendor')
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Carrier rates relationship
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingZones::route('/'),
            'create' => Pages\CreateShippingZone::route('/create'),
            'edit' => Pages\EditShippingZone::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('vendor')
            ->orderBy('vendor_id')
            ->orderBy('name');
    }
}