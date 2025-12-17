<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Support\Str;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.groups.catalog');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.nav.vendors');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Vendor Profile')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Owner')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('currency_id')
                        ->label('Currency')
                        ->relationship('currency', 'code')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('store_name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                            if ($operation === 'create') {
                                $set('slug', Str::slug($state));
                            }
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->disabled()
                        ->dehydrated()
                        ->unique(Vendor::class, 'slug', ignoreRecord: true),
                    Forms\Components\Textarea::make('description')
                        ->rows(4),
                    Forms\Components\FileUpload::make('logo_path')
                        ->label('Logo')
                        ->disk('public_uploads')
                        ->directory('vendors')
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->nullable(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Location Information')
                ->schema([
                    Forms\Components\TextInput::make('address')
                        ->label('Street Address')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('city')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('state')
                        ->label('State/Province')
                        ->maxLength(100),
                    Forms\Components\Select::make('country')
                        ->options([
                            'GN' => 'Guinea',
                            'USA' => 'United States',
                            'CN' => 'China',
                        ])
                        ->default('GN')
                        ->searchable(),
                    Forms\Components\TextInput::make('zip_code')
                        ->label('ZIP/Postal Code')
                        ->maxLength(20),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('latitude')
                                ->numeric()
                                ->step('any')
                                ->helperText('Format: 9.516667 for Conakry'),
                            Forms\Components\TextInput::make('longitude')
                                ->numeric()
                                ->step('any')
                                ->helperText('Format: -13.716667 for Conakry'),
                        ]),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public_uploads')
                    ->visibility('public')
                    ->circular()
                    ->size(40),
                Tables\Columns\TextColumn::make('currency.code')
                    ->label('Currency')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('City')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->label('Country')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'GN' => 'Guinea',
                        'USA' => 'United States',
                        'CN' => 'China',
                        default => $state,
                    })
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Filter::make('approved')
                    ->label('Approved Vendors')
                    ->query(fn ($query) => $query->whereNotNull('approved_at')),
                Tables\Filters\SelectFilter::make('country')
                    ->options([
                        'GN' => 'Guinea',
                        'USA' => 'United States',
                        'CN' => 'China',
                    ])
                    ->label('Country'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            // Future relation managers
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}