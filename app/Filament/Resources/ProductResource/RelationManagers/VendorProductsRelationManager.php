<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\VendorResource;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorProducts';

    protected static ?string $title = 'Vendor Offers';

    protected static ?string $icon = 'heroicon-o-tag';

    /**
     * Nothing here was ever scoped by panel — a vendor opening this tab on a
     * product they own could pick ANY vendor in the free 'vendor_id' select
     * below, plant a listing under another vendor's name, or bulk-delete /
     * activate / deactivate rows belonging to a different vendor entirely on a
     * product shared between them. Admin still manages any vendor's offer;
     * a vendor is locked to their own.
     */
    protected static function isVendorPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'vendor';
    }

    protected static function vendorId(): ?int
    {
        $user = Filament::auth()->user();

        return $user?->vendor->id ?? $user?->vendor_id ?? null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vendor_id')
                    ->relationship('vendor', 'store_name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Vendor')
                    ->default(fn () => static::isVendorPanel() ? static::vendorId() : null)
                    ->disabled(fn () => static::isVendorPanel())
                    ->dehydrated(),

                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->label('Selling Price'),

                Forms\Components\TextInput::make('sale_price')
                    ->numeric()
                    ->label('Sale Price')
                    ->helperText('Optional promotional price'),

                Forms\Components\TextInput::make('stock')
                    ->numeric()
                    ->required()
                    ->label('Available Stock'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active Offer')
                    ->default(true),

                Forms\Components\KeyValue::make('variation_json')
                    ->label('Variation Attributes')
                    ->keyLabel('Attribute')
                    ->valueLabel('Value')
                    ->helperText('Ex: color: Black, size: XL')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('vendor.store_name')
            ->modifyQueryUsing(function (Builder $query) {
                if (static::isVendorPanel()) {
                    $query->where('vendor_id', static::vendorId());
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('vendor.store_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state, $record) => 
                        number_format($state, 2) . ' ' . ($record->vendor?->currency?->code ?? 'USD')
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Sale Price')
                    ->formatStateUsing(fn ($state, $record) => 
                        $state ? number_format($state, 2) . ' ' . ($record->vendor?->currency?->code ?? 'USD') : '-'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn ($state) => $state <= 10 ? 'danger' : ($state <= 50 ? 'warning' : 'success')),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor_id')
                    ->relationship('vendor', 'store_name')
                    ->label('Vendor')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Offers'),

                Tables\Filters\Filter::make('on_sale')
                    ->label('On Sale')
                    ->query(fn (Builder $query) => $query->whereNotNull('sale_price')),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock (< 10)')
                    ->query(fn (Builder $query) => $query->where('stock', '<=', 10)),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) => $query->where('stock', '=', 0)),
            ])
            ->headerActions([
                // Admin can create new vendor offers if needed
                Tables\Actions\CreateAction::make()
                    ->label('Add Vendor Offer'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}