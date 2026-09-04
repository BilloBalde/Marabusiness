<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\VendorShippingRateResource\Pages;
use App\Models\Locality;
use App\Models\VendorShippingRate;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lets a vendor price the localities it delivers to. Until now vendors had no
 * shipping configuration screen at all — zones and carrier rates were admin-only, or
 * generated on the fly by the carrier calculator.
 */
class VendorShippingRateResource extends Resource
{
    protected static ?string $model = VendorShippingRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Livraison';
    protected static ?string $navigationLabel = 'Tarifs par localité';
    protected static ?string $pluralLabel = 'Tarifs par localité';
    protected static ?string $modelLabel = 'tarif de livraison';
    protected static ?string $slug = 'shipping-rates';

    protected static function vendor()
    {
        return Filament::auth()->user()?->vendor;
    }

    public static function getEloquentQuery(): Builder
    {
        $vendorId = static::vendor()?->id;

        return parent::getEloquentQuery()
            ->when($vendorId, fn (Builder $q) => $q->where('vendor_id', $vendorId))
            ->when(! $vendorId, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->with(['locality.parent', 'tiers']);
    }

    public static function canViewAny(): bool
    {
        return static::vendor() !== null;
    }

    protected static function currencyCode(): string
    {
        return static::vendor()?->currency?->code ?? 'USD';
    }

    public static function form(Form $form): Form
    {
        $currency = static::currencyCode();

        return $form->schema([
            Forms\Components\Section::make('Localité et tarif')
                ->description('Le montant est exprimé dans votre devise et s\'applique quelle que soit la commande, sauf si vous ajoutez des paliers ci-dessous.')
                ->schema([
                    Forms\Components\Select::make('locality_id')
                        ->label('Localité desservie')
                        ->options(fn () => static::localityOptions())
                        ->searchable()
                        ->required()
                        // A vendor prices a given locality once; the unique index enforces it.
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn ($rule) => $rule->where('vendor_id', static::vendor()?->id),
                        ),

                    Forms\Components\TextInput::make('amount')
                        ->label('Prix de livraison')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->suffix($currency),

                    Forms\Components\TextInput::make('delivery_days')
                        ->label('Délai (jours)')
                        ->numeric()
                        ->minValue(0)
                        ->default(2)
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Paliers de quantité')
                ->description('Facultatif. Un palier qui correspond à la quantité commandée remplace le prix ci-dessus. Laissez la quantité maximale vide pour « et au-delà ».')
                ->schema([
                    Forms\Components\Repeater::make('tiers')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('min_qty')
                                ->label('À partir de')
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            Forms\Components\TextInput::make('max_qty')
                                ->label('Jusqu\'à')
                                ->numeric()
                                ->minValue(1)
                                ->helperText('Vide = et au-delà'),

                            Forms\Components\TextInput::make('amount')
                                ->label('Prix')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->suffix($currency),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Ajouter un palier')
                        ->reorderable(false),
                ])
                ->collapsed(fn (?VendorShippingRate $record) => $record === null),
        ]);
    }

    public static function table(Table $table): Table
    {
        $currency = static::currencyCode();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('locality.name')
                    ->label('Localité')
                    ->description(fn (VendorShippingRate $record) => $record->locality?->parent?->name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Prix')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' ' . $currency)
                    ->sortable(),

                Tables\Columns\TextColumn::make('tiers_count')
                    ->label('Paliers')
                    ->counts('tiers')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('delivery_days')
                    ->label('Délai')
                    ->suffix(' j')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('locality_id')
            ->emptyStateHeading('Aucune localité tarifée')
            ->emptyStateDescription('Ajoutez les localités que vous livrez et leur prix. Partout ailleurs, votre montant par défaut s\'applique.');
    }

    /**
     * Grouped "Commune (Préfecture)" labels so places sharing a name stay distinguishable.
     */
    protected static function localityOptions(): array
    {
        return Locality::selectable()
            ->with('parent')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Locality $locality) => [$locality->id => $locality->full_name])
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVendorShippingRates::route('/'),
            'create' => Pages\CreateVendorShippingRate::route('/create'),
            'edit'   => Pages\EditVendorShippingRate::route('/{record}/edit'),
        ];
    }
}
