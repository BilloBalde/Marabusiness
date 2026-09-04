<?php

namespace App\Filament\Vendor\Resources\VendorProductResource\Pages\Components;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

class VariationGrid extends Component
{
    protected string $view = 'filament.vendor.resources.vendor-product-resource.pages.components.variation-grid';

    public static function make(): static
    {
        return app(static::class);
    }

    public function getSchema(array $combinations): array
    {
        $schema = [];
        
        foreach ($combinations as $index => $combination) {
            $label = collect($combination)->map(fn($v, $k) => "<strong>$k:</strong> $v")->join(', ');
            
            $schema[] = Forms\Components\Section::make($label)
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextInput::make("variations.{$index}.price")
                                ->label('Price')
                                ->numeric()
                                ->required()
                                ->default(fn($get) => $get('../price') ?? 0),

                            TextInput::make("variations.{$index}.sale_price")
                                ->label('Sale Price')
                                ->numeric()
                                ->visible(fn($get) => $get('../../on_sale')),

                            TextInput::make("variations.{$index}.stock")
                                ->label('Stock')
                                ->numeric()
                                ->required()
                                ->default(0),

                            TextInput::make("variations.{$index}.sku")
                                ->label('SKU')
                                ->placeholder('Auto-generated'),
                        ]),
                ])
                ->collapsible()
                ->collapsed($index > 2);
        }
        
        return $schema;
    }
}