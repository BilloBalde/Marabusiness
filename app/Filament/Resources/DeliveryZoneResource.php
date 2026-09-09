<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryZoneResource\Pages;
use App\Models\DeliveryZonePrice;
use App\Models\Locality;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The shared delivery-zone price catalogue: a locality (country > region > city) priced
 * once, in USD, visible and editable by every vendor and by the admin — not scoped to
 * whoever created it. Registered in both the admin and the vendor panel, exactly like
 * ProductResource / CategoryResource / BrandResource already are
 * (see VendorPanelProvider::resources()).
 *
 * Deliberately different from every other vendor-facing resource in this codebase:
 * there is no getEloquentQuery() scoping by vendor_id, because there is no "own" row
 * here — a vendor can see and price a zone another vendor already delivers to. Deletion
 * is admin-only (see canDelete()) so one vendor cannot remove pricing others rely on.
 */
class DeliveryZoneResource extends Resource
{
    protected static ?string $model = DeliveryZonePrice::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Zones de livraison';
    protected static ?string $pluralLabel = 'Zones de livraison';
    protected static ?string $modelLabel = 'zone de livraison';
    protected static ?string $slug = 'delivery-zones';

    /**
     * Public: the List/Edit pages call this from outside the resource to decide
     * whether to show the vendor-only settings action and the admin-only delete button.
     */
    public static function isAdmin(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    public static function getNavigationGroup(): ?string
    {
        return static::isAdmin() ? 'Livraison' : null;
    }

    public static function canDelete($record = null): bool
    {
        return static::isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return static::isAdmin();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Every vendor and the admin see the exact same rows — intentionally unscoped.
        return parent::getEloquentQuery()->with(['locality.parent.parent', 'tiers', 'createdBy']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Zone géographique')
                ->description('Choisissez le pays, puis la localité à tarifer. Une fois enregistré, ce prix est visible et utilisé par tous les vendeurs pour cette zone.')
                ->schema([
                    Forms\Components\Select::make('country_filter')
                        ->label('Pays')
                        ->options(fn () => Locality::where('type', Locality::TYPE_COUNTRY)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('locality_id', null))
                        ->default(fn ($record) => static::countryIdFor($record))
                        ->required(fn (Get $get) => ! $get('locality_id'))
                        ->helperText('Le pays de destination du client, pas celui du vendeur.'),

                    Forms\Components\Select::make('locality_id')
                        ->label('Localité')
                        ->options(function (Get $get) {
                            $countryId = $get('country_filter');

                            if (! $countryId) {
                                return [];
                            }

                            return static::localitiesUnder((int) $countryId)
                                ->mapWithKeys(fn (Locality $l) => [$l->id => static::labelWithinCountry($l)]);
                        })
                        ->searchable()
                        ->required()
                        ->disabled(fn (Get $get) => ! $get('country_filter'))
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn ($rule) => $rule,
                        )
                        ->validationMessages([
                            'unique' => 'Cette localité a déjà un prix partagé — modifiez-le plutôt que d\'en créer un second.',
                        ]),
                ])
                ->columns(2),

            Forms\Components\Section::make('Prix partagé')
                ->description('Exprimé en USD : c\'est ce qui permet à des vendeurs facturant en devises différentes de voir le même prix pour une même zone.')
                ->schema([
                    Forms\Components\TextInput::make('price_usd')
                        ->label('Prix de livraison')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->suffix('USD'),

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
                ->columns(3),

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

                            Forms\Components\TextInput::make('price_usd')
                                ->label('Prix')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->suffix('USD'),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Ajouter un palier')
                        ->reorderable(false),
                ])
                ->collapsed(fn (?DeliveryZonePrice $record) => $record === null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('locality.name')
                    ->label('Localité')
                    ->description(fn (DeliveryZonePrice $record) => $record->locality?->parent?->name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('country_name')
                    ->label('Pays')
                    ->state(fn (DeliveryZonePrice $record) => static::countryNameFor($record))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price_usd')
                    ->label('Prix')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' USD')
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

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Renseigné par')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country')
                    ->label('Pays')
                    ->options(fn () => Locality::where('type', Locality::TYPE_COUNTRY)->orderBy('name')->pluck('name', 'id'))
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        $ids = static::localitiesUnder((int) $data['value'])->pluck('id');

                        return $query->whereIn('locality_id', $ids);
                    }),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->visible(fn () => static::isAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->visible(fn () => static::isAdmin()),
            ])
            ->defaultSort('locality_id')
            ->emptyStateHeading('Aucune zone tarifée')
            ->emptyStateDescription('Ajoutez une localité et son prix. Ce prix sera visible et appliqué par tous les vendeurs pour cette zone.');
    }

    /**
     * Every selectable locality nested anywhere under a given country node.
     */
    protected static function localitiesUnder(int $countryId): \Illuminate\Support\Collection
    {
        $regionIds = Locality::where('parent_id', $countryId)->pluck('id');
        $allIds = $regionIds->push($countryId);

        // One extra hop: region -> city (Guinea's shape). A country with cities
        // directly attached (no region level) is already covered by $regionIds.
        $cityParentIds = Locality::whereIn('parent_id', $regionIds)->pluck('id');

        return Locality::selectable()
            ->where(fn ($q) => $q
                ->whereIn('parent_id', $allIds)
                ->orWhereIn('parent_id', $cityParentIds))
            ->orderBy('name')
            ->get();
    }

    protected static function labelWithinCountry(Locality $locality): string
    {
        return $locality->parent && $locality->parent->type !== Locality::TYPE_COUNTRY
            ? "{$locality->name} ({$locality->parent->name})"
            : $locality->name;
    }

    /**
     * Public: EditDeliveryZone::mutateFormDataBeforeFill() calls this from outside the
     * resource to pre-fill the virtual country_filter field, which has no counterpart
     * column on the record for Filament's own default-value fill to find.
     */
    public static function countryIdFor($record): ?int
    {
        return static::countryNodeFor($record)?->id;
    }

    protected static function countryNameFor($record): ?string
    {
        return static::countryNodeFor($record)?->name;
    }

    /**
     * Walks up from the priced locality to its country root — robust to a locality
     * sitting one level under its country (no region in between) or several, since
     * Guinea's own hierarchy (country > region > prefecture/commune) is one level
     * deeper than a country with cities attached directly.
     */
    protected static function countryNodeFor($record): ?Locality
    {
        if (! $record instanceof DeliveryZonePrice) {
            return null;
        }

        $node = $record->locality;

        while ($node && $node->type !== Locality::TYPE_COUNTRY) {
            $node = $node->parent;
        }

        return $node;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDeliveryZones::route('/'),
            'create' => Pages\CreateDeliveryZone::route('/create'),
            'edit'   => Pages\EditDeliveryZone::route('/{record}/edit'),
        ];
    }
}
