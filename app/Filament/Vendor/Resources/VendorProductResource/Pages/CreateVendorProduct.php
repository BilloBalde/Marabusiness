<?php

namespace App\Filament\Vendor\Resources\VendorProductResource\Pages;

use App\Filament\Vendor\Resources\VendorProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class CreateVendorProduct extends CreateRecord
{
    protected static string $resource = VendorProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['vendor_id'] = $user->vendor_id ?? $user->vendor?->id;
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $hasVariations = $data['has_variations'] ?? false;
        
        if ($hasVariations) {
            // Create main vendor product with variations
            $vendorProduct = static::getModel()::create([
                'vendor_id' => $data['vendor_id'],
                'product_id' => $data['product_id'],
                'price' => $data['price'] ?? 0, // Save the price
                'purchase_price' => $data['purchase_price'] ?? null, // Save purchase price
                'has_variations' => true,
                'variation_matrix' => $data['variation_matrix'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'on_sale' => false, // Variations handle sale prices individually
                'sale_start' => null,
                'sale_end' => null,
                'sale_price' => null, // No sale price for variation products
                'stock' => 0, // Stock is per variation
            ]);
            
            // Variations will be created in ManageVariations page
            
            return $vendorProduct;
        } else {
            // Simple product without variations
            return static::getModel()::create([
                'vendor_id' => $data['vendor_id'],
                'product_id' => $data['product_id'],
                'price' => $data['price'] ?? 0,
                'sale_price' => ($data['on_sale'] ?? false) ? ($data['sale_price'] ?? null) : null,
                'purchase_price' => $data['purchase_price'] ?? null,
                'stock' => $data['stock'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'on_sale' => $data['on_sale'] ?? false,
                'sale_start' => ($data['on_sale'] ?? false) ? ($data['sale_start'] ?? null) : null,
                'sale_end' => ($data['on_sale'] ?? false) ? ($data['sale_end'] ?? null) : null,
                'has_variations' => false,
                'variation_matrix' => null,
            ]);
        }
    }
}