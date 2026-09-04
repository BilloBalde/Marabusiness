<?php

namespace App\Filament\Vendor\Resources\VendorProductResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\RelationManagers\RelationManager;

class VendorProductWholesalesRelationManager extends RelationManager
{
    // Relation name on VendorProduct model
    protected static string $relationship = 'wholesaleTiers';

    protected static ?string $title = 'Wholesale Prices';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ! (bool) $ownerRecord->has_variations;
    }


    /**
     * Get vendor currency code for this product (ownerRecord).
     */
    public function getVendorCurrency(): string
    {
        // ownerRecord = current VendorProduct
        return $this->ownerRecord->vendor->currency->code ?? 'USD';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([

                Forms\Components\TextInput::make('min_qty')
                    ->label('Min Qty')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('max_qty')
                    ->label('Max Qty')
                    ->numeric()
                    ->nullable(),

                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->label(function ($livewire) {
                        /** @var self $livewire */
                        return 'Wholesale Price (' . $livewire->getVendorCurrency() . ')';
                    }),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('min_qty')
                    ->label('Min Qty')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_qty')
                    ->label('Max Qty')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(function ($state, $record, $livewire) {
                        /** @var self $livewire */
                        $currency = $livewire->getVendorCurrency();
                        return number_format($state, 2) . ' ' . $currency;
                    })
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Tier'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('min_qty', 'asc');
    }
}
