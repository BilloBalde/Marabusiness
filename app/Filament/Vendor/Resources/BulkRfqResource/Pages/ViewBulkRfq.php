<?php

// app/Filament/Vendor/Resources/BulkRfqResource/Pages/ViewBulkRfq.php
namespace App\Filament\Vendor\Resources\BulkRfqResource\Pages;

use App\Filament\Vendor\Resources\BulkRfqResource;
use App\Models\BulkRfq;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewBulkRfq extends ViewRecord
{
    protected static string $resource = BulkRfqResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('quote')
                ->label('Submit Quote')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->url(fn (): string => BulkRfqResource::getUrl('quote', ['record' => $this->record]))
                ->visible(fn (): bool => $this->record->status === 'pending'),
            Actions\DeleteAction::make(),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('RFQ Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('product.name')
                            ->label('Product'),
                            
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Buyer'),
                            
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Buyer Email'),
                            
                        Infolists\Components\TextEntry::make('user.phone')
                            ->label('Buyer Phone'),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Order Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('quantity')
                            ->label('Quantity')
                            ->formatStateUsing(fn ($state): string => number_format($state) . ' units'),
                            
                        Infolists\Components\TextEntry::make('target_price')
                            ->label('Target Price')
                            ->money('USD'),
                            
                        Infolists\Components\TextEntry::make('currency'),
                            
                        Infolists\Components\IconEntry::make('needs_customization')
                            ->label('Customization Needed')
                            ->boolean(),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Shipping Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('shipping_country'),
                        Infolists\Components\TextEntry::make('shipping_city'),
                        Infolists\Components\TextEntry::make('shipping_port'),
                    ])->columns(3),
                    
                Infolists\Components\Section::make('Customization Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('customization_notes')
                            ->columnSpanFull()
                            ->prose(),
                    ])
                    ->visible(fn ($record): bool => !empty($record->customization_notes)),
                    
                Infolists\Components\Section::make('Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'quoted' => 'success',
                                'accepted' => 'primary',
                                'rejected' => 'danger',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),
                            
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                            
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])->columns(3),
            ]);
    }
}