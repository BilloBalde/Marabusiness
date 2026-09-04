<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionSettingResource\Pages;
use App\Filament\Resources\CommissionSettingResource\RelationManagers;
use App\Models\CommissionSetting;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommissionSettingResource extends Resource
{
    protected static ?string $model = CommissionSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-percent-badge';
    protected static ?string $navigationGroup = 'Finance Settings';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vendor_id')
                    ->relationship('vendor', 'store_name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->helperText('Leave empty for global setting'),
                    
                Forms\Components\Select::make('commission_type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                    ])
                    ->required()
                    ->live(),
                    
                Forms\Components\TextInput::make('commission_rate')
                    ->numeric()
                    ->required()
                    ->suffix(function ($get) {
                        $currency = static::getCurrencyData($get('vendor_id'));

                        if ($get('commission_type') === 'percentage') {
                            return '%';
                        }

                        return $currency['symbol'] !== '' ? $currency['symbol'] : $currency['code'];
                    }),
                    
                Forms\Components\TextInput::make('minimum_amount')
                    ->numeric()
                    ->nullable()
                    ->prefix(fn($get) => static::getCurrencyData($get('vendor_id'))['symbol'] ?: null)
                    ->suffix(fn($get) => static::getCurrencyData($get('vendor_id'))['symbol']
                        ? null
                        : static::getCurrencyData($get('vendor_id'))['code']
                    ),
                    
                Forms\Components\TextInput::make('maximum_amount')
                    ->numeric()
                    ->nullable()
                    ->prefix(fn($get) => static::getCurrencyData($get('vendor_id'))['symbol'] ?: null)
                    ->suffix(fn($get) => static::getCurrencyData($get('vendor_id'))['symbol']
                        ? null
                        : static::getCurrencyData($get('vendor_id'))['code']
                    ),
                    
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'stripe' => 'Stripe',
                        'cash' => 'Cash',
                        'om' => 'Orange Money',
                    ])
                    ->nullable()
                    ->helperText('Leave empty to apply to all payment methods'),
                    
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
                    
                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor.store_name')
                    ->sortable()
                    ->placeholder('Global'),
                    
                Tables\Columns\TextColumn::make('commission_type')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state)),
                    
                Tables\Columns\TextColumn::make('commission_rate')
                    ->formatStateUsing(function ($record) {
                        return $record->commission_type === 'percentage' 
                            ? $record->commission_rate . '%'
                            : static::formatCurrency($record->commission_rate, $record->vendor_id);
                    }),
                    
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->placeholder('All Methods'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor')
                    ->relationship('vendor', 'store_name')
                    ->multiple()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('commission_type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissionSettings::route('/'),
            'create' => Pages\CreateCommissionSetting::route('/create'),
            'edit' => Pages\EditCommissionSetting::route('/{record}/edit'),
        ];
    }

    protected static function getCurrencyData(?int $vendorId): array
    {
        $defaultCode = strtoupper((string) config('app.currency', 'USD'));

        if (! $vendorId) {
            return ['symbol' => '', 'code' => $defaultCode];
        }

        $currency = Vendor::with('currency')->find($vendorId)?->currency;

        if (! $currency) {
            return ['symbol' => '', 'code' => $defaultCode];
        }

        $symbol = trim((string) $currency->symbol);
        $code = strtoupper(trim((string) $currency->code));

        return [
            'symbol' => $symbol,
            'code' => $code !== '' ? $code : $defaultCode,
        ];
    }

    protected static function formatCurrency(?float $amount, ?int $vendorId): string
    {
        $data = static::getCurrencyData($vendorId);
        $formatted = number_format((float) $amount, 2);

        if ($data['symbol'] !== '') {
            return $data['symbol'] . $formatted;
        }

        if ($data['code'] !== '') {
            return $formatted . ' ' . $data['code'];
        }

        return $formatted;
    }
}
