<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AddressRelationManager extends RelationManager
{
    protected static string $relationship = 'address';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                    ]),

                TextInput::make('phone')
                    ->required()
                    ->maxLength(255),

                TextArea::make('street_address')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Grid::make(3)
                    ->schema([
                        TextInput::make('country')
                            ->required()
                            ->default('United States') // 根据需要调整默认值
                            ->maxLength(255),

                        TextInput::make('city')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('state')
                            ->required()
                            ->maxLength(255),

                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        TextInput::make('zip_code')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('zone')
                            ->label('Delivery Zone')
                            ->placeholder('Auto-calculated or manually set')
                            ->helperText('Zone based on vendor location')
                            ->nullable()
                            ->maxLength(100),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->minValue(-90)
                            ->maxValue(90)
                            ->nullable()
                            ->suffix('°')
                            ->helperText('Decimal degrees, e.g., 40.7128'),
                        
                        Forms\Components\TextInput::make('longitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->minValue(-180)
                            ->maxValue(180)
                            ->nullable()
                            ->suffix('°')
                            ->helperText('Decimal degrees, e.g., -74.0060'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('street_address')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Customer Name'),

                TextColumn::make('phone')
                    ->searchable(),

                TextColumn::make('city')
                    ->searchable(),

                TextColumn::make('country')
                    ->searchable(),

                TextColumn::make('state')
                    ->searchable(),

                TextColumn::make('zip_code')
                    ->searchable(),

                TextColumn::make('zone')
                    ->label('Zone')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Zone A' => 'success',
                        'Zone B' => 'warning',
                        'Zone C' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('street_address')
                    ->label('Address')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),

                // 可选：显示坐标
                TextColumn::make('coordinates')
                    ->label('Coordinates')
                    ->getStateUsing(function ($record) {
                        if ($record->latitude && $record->longitude) {
                            return "{$record->latitude}, {$record->longitude}";
                        }
                        return 'N/A';
                    })
                    ->copyable() // 允许复制坐标
                    ->copyMessage('Coordinates copied to clipboard')
                    ->copyMessageDuration(1500),
            ])
            ->filters([
                // 添加筛选器
                Tables\Filters\SelectFilter::make('zone')
                    ->options([
                        'Zone A' => 'Zone A',
                        'Zone B' => 'Zone B',
                        'Zone C' => 'Zone C',
                    ])
                    ->label('Delivery Zone'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                // 可选：添加地图查看动作
                Tables\Actions\Action::make('viewOnMap')
                    ->label('Map')
                    ->icon('heroicon-o-map')
                    ->url(fn ($record): string => 
                        $record->latitude && $record->longitude 
                            ? "https://www.google.com/maps?q={$record->latitude},{$record->longitude}"
                            : '#'
                    )
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => 
                        !is_null($record->latitude) && !is_null($record->longitude)
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // Helper method for zone calculation
    private function calculateZoneForAddress($address): void
    {
        // Get vendor location (you need to implement this based on your business logic)
        $vendorLocation = $this->getVendorLocation();
        
        if (!$vendorLocation || !$address->latitude || !$address->longitude) {
            return;
        }
        
        $distance = $this->calculateDistance(
            $address->latitude,
            $address->longitude,
            $vendorLocation['latitude'],
            $vendorLocation['longitude']
        );
        
        // Assign zone based on distance
        if ($distance < 5) {
            $zone = 'Zone A';
        } elseif ($distance < 15) {
            $zone = 'Zone B';
        } else {
            $zone = 'Zone C';
        }
        
        $address->update(['zone' => $zone]);
    }
    
    private function getVendorLocation(): ?array
    {
        // Implement based on your vendor logic
        // This could come from the Order, a Settings table, etc.
        return [
            'latitude' => config('vendor.default_latitude', 0),
            'longitude' => config('vendor.default_longitude', 0),
        ];
    }
    
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // Kilometers
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}