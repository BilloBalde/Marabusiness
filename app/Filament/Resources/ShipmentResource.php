<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentResource\Pages;
use App\Models\Shipment;
use App\Services\Shipping\CarrierTrackingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Order;
use App\Models\ShippingCarrierRate;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.shipments');
    }

    protected static ?string $recordTitleAttribute = 'tracking_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shipment details')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->relationship(
                                'order',
                                'order_number',
                                modifyQueryUsing: function (Builder $query) {
                                    if ($vendorId = auth()->user()?->vendor?->id) {
                                        $query->where('vendor_id', $vendorId);
                                    }
                                }
                            )
                            ->label('Order')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                $set('carrier', null);
                            })
                            ->required(),
                        Forms\Components\Select::make('carrier')
                            ->options(function (Get $get) {
                                $orderId = $get('order_id');
                                if (!$orderId) {
                                    return [];
                                }

                                $vendorId = Order::whereKey($orderId)->value('vendor_id');
                                if (!$vendorId) {
                                    return [];
                                }

                                $carriers = ShippingCarrierRate::query()
                                    ->select('carrier')
                                    ->where('vendor_id', $vendorId)
                                    ->active()
                                    ->get()
                                    ->mapWithKeys(fn ($rate) => [
                                        $rate->carrier => ShippingCarrierRate::CARRIERS[$rate->carrier] ?? $rate->carrier_name,
                                    ])
                                    ->toArray();

                                // Fallback to defaults if no vendor carriers found
                                return !empty($carriers) ? $carriers : Shipment::CARRIERS;
                            })
                            ->helperText('Pick a carrier configured for this order\'s vendor.')
                            ->placeholder('Select a carrier')
                            ->reactive()
                            ->disabled(fn (Get $get) => blank($get('order_id')))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('tracking_number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('status')
                            ->default('pending')
                            ->maxLength(255)
                            ->helperText('Will be overwritten when syncing with carrier.'),
                        Forms\Components\TextInput::make('current_location')
                            ->maxLength(255)
                            ->label('Latest location'),
                        Forms\Components\DateTimePicker::make('estimated_delivery_at')
                            ->label('Estimated delivery'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')->label('Order')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('carrier')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tracking_number')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('current_location')->label('Location')->toggleable(),
                Tables\Columns\TextColumn::make('estimated_delivery_at')->dateTime()->label('ETA')->toggleable(),
                Tables\Columns\TextColumn::make('last_synced_at')->dateTime()->label('Last Sync')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('carrier')->options(Shipment::CARRIERS),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'in_transit' => 'In transit',
                    'delivered' => 'Delivered',
                    'exception' => 'Exception',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('syncTracking')
                    ->label('Sync Tracking')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (Shipment $record) {
                        try {
                            app(CarrierTrackingService::class)->sync($record);
                            Notification::make()
                                ->title('Tracking updated')
                                ->success()
                                ->send();
                        } catch (\Throwable $exception) {
                            Notification::make()
                                ->title('Sync failed')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Shipment $record) => $record->carrier === 'dhl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('syncSelected')
                    ->label('Sync Selected')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $service = app(CarrierTrackingService::class);
                        foreach ($records as $record) {
                            try {
                                $service->sync($record);
                            } catch (\Throwable $exception) {
                                report($exception);
                            }
                        }
                        Notification::make()
                            ->title('Tracking sync triggered')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn () => true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
        ];
    }
}
