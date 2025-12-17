<?php

// app/Filament/Vendor/Resources/BulkRfqResource.php
namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Resources\BulkRfqResource\Pages;
use App\Models\BulkRfq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BulkRfqResource extends Resource
{
    protected static ?string $model = BulkRfq::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Sales';
    
    protected static ?int $navigationSort = 3;
    public static function canViewNavigation(): bool
    {
        return false;
    }
    
    // Or to hide completely (including from search)
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('RFQ Information')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->suffix('units'),
                        
                        Forms\Components\TextInput::make('target_price')
                            ->numeric()
                            ->prefix('$')
                            ->nullable(),
                            
                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GNF' => 'GNF',
                            ])
                            ->default('USD'),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Shipping Details')
                    ->schema([
                        Forms\Components\TextInput::make('shipping_country'),
                        Forms\Components\TextInput::make('shipping_city'),
                        Forms\Components\TextInput::make('shipping_port'),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Customization')
                    ->schema([
                        Forms\Components\Toggle::make('needs_customization'),
                        Forms\Components\Textarea::make('customization_notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Buyer')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('quantity')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('target_price')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'quoted',
                        'primary' => 'accepted',
                        'danger' => 'rejected',
                    ]),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'quoted' => 'Quoted',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (BulkRfq $record): string => BulkRfqResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
                    
                Tables\Actions\Action::make('quote')
                    ->url(fn (BulkRfq $record): string => BulkRfqResource::getUrl('quote', ['record' => $record]))
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (BulkRfq $record): bool => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('vendor_id', auth()->user()->vendor->id)
            ->orderBy('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBulkRfqs::route('/'),
            'view' => Pages\ViewBulkRfq::route('/{record}'),
            'edit' => Pages\EditBulkRfq::route('/{record}/edit'),
            'create' => Pages\CreateBulkRfq::route('/create'),
            'quote' => Pages\QuoteBulkRfq::route('/{record}/quote'),
        ];
    }
}