<?php

namespace App\Filament\Vendor\Resources\VendorProductResource\Pages;

use App\Filament\Vendor\Resources\VendorProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class ManageVariations extends EditRecord
{
    protected static string $resource = VendorProductResource::class;
    
    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        if (!$this->record->has_variations) {
            Notification::make()
                ->title('This product does not have variations')
                ->danger()
                ->send();
                
            $this->redirect(VendorProductResource::getUrl('edit', ['record' => $this->record]));
        }
        
        // If no variations exist yet, generate them from matrix
        if (!$this->record->variations()->exists()) {
            $this->generateVariationsFromMatrix();
        }
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Repeater::make('variations')
                    ->relationship('variations')
                    ->schema([
                        Forms\Components\KeyValue::make('attributes')
                            ->label('Variation Attributes')
                            ->keyLabel('Attribute')
                            ->valueLabel('Value')
                            ->columnSpanFull()
                            ->disabled(),
                            
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->required()
                                    ->prefix($this->record->vendor->currency->symbol ?? '$'),
                                    
                                Forms\Components\TextInput::make('sale_price')
                                    ->label('Sale Price')
                                    ->numeric()
                                    ->prefix($this->record->vendor->currency->symbol ?? '$'),
                                    
                                Forms\Components\TextInput::make('stock')
                                    ->label('Stock')
                                    ->numeric()
                                    ->required()
                                    ->default(0),
                                    
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->helperText('Unique identifier'),
                            ]),
                        Forms\Components\Section::make('Wholesale Tiers')
                            ->schema([
                                Forms\Components\Repeater::make('wholesaleTiers')
                                    ->relationship('wholesaleTiers')
                                    ->label('Wholesale Prices')
                                    ->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('min_qty')
                                                ->label('Min Qty')
                                                ->numeric()
                                                ->required(),

                                            Forms\Components\TextInput::make('max_qty')
                                                ->label('Max Qty')
                                                ->numeric()
                                                ->nullable()
                                                ->helperText('Leave empty for “no limit”'),

                                            Forms\Components\TextInput::make('price')
                                                ->label('Wholesale Price')
                                                ->numeric()
                                                ->required()
                                                ->rule('regex:/^\d+(\.\d{1,2})?$/') // ✅ only 2 decimals
                                                ->prefix($this->record->vendor->currency->symbol ?? '$'),
                                        ]),
                                    ])
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(function (array $state): ?string {
                                        $min = $state['min_qty'] ?? null;
                                        $max = $state['max_qty'] ?? null;
                                        if (!$min) return 'Tier';
                                        return $max ? "{$min} - {$max}" : "{$min}+";
                                    }),
                            ])
                            ->collapsible()
                            ->collapsed(),

                    ])
                    ->columnSpanFull()
                    ->reorderable()
                    ->itemLabel(fn(array $state): ?string => 
                        collect($state['attributes'] ?? [])
                            ->map(fn($v, $k) => "$k: $v")
                            ->join(', ') ?: 'New Variation'
                    ),
            ]);
    }
    
    private function generateVariationsFromMatrix(): void
    {
        $matrix = $this->record->variation_matrix ?? [];
        
        if (empty($matrix)) {
            Notification::make()
                ->title('No variation matrix defined')
                ->warning()
                ->send();
            return;
        }
        
        $combinations = VendorProductResource::generateCombinations($matrix);
        
        foreach ($combinations as $combo) {
            $this->record->variations()->create([
                'attributes' => $combo,
                'price' => 0, // Default price
                'stock' => 0, // Default stock
                'sku' => $this->generateSku($combo),
            ]);
        }
        
        Notification::make()
            ->title(count($combinations) . ' variations generated')
            ->success()
            ->send();
            
        // Refresh the form
        $this->form->fill([
            'variations' => $this->record->variations->map(function ($variation) {
                return [
                    'id' => $variation->id,
                    'attributes' => $variation->attributes,
                    'price' => $variation->price,
                    'sale_price' => $variation->sale_price,
                    'stock' => $variation->stock,
                    'sku' => $variation->sku,
                ];
            })->toArray(),
        ]);
    }
    
    private function generateSku(array $attributes): string
    {
        $product = $this->record->product;
        $attributeCodes = collect($attributes)
            ->map(fn($v) => strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($v, 0, 3))))
            ->join('');
        
        return ($product->id ?? 'P') . '-' . 
               ($this->record->vendor_id ?? 'V') . '-' . 
               $attributeCodes;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate')
                ->label('Generate from Matrix')
                ->action('generateVariationsFromMatrix')
                ->color('secondary')
                ->visible(fn() => $this->record->variation_matrix && !$this->record->variations()->exists()),
                
            Actions\Action::make('back')
                ->label('Back to Product')
                ->url(fn() => VendorProductResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
    
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Variations updated')
            ->success();
    }
}