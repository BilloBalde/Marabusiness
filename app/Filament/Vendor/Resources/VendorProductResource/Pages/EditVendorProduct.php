<?php

namespace App\Filament\Vendor\Resources\VendorProductResource\Pages;

use App\Filament\Vendor\Resources\VendorProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVendorProduct extends EditRecord
{
    protected static string $resource = VendorProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        \Log::info('Creating VendorProduct', [
            'has_variations' => $data['has_variations'] ?? false,
            'price' => $data['price'] ?? 'NOT SET',
            'purchase_price' => $data['purchase_price'] ?? 'NOT SET',
            'all_data' => $data,
        ]);
        $hasVariations = $data['has_variations'] ?? false;
        
        // Update the main record - ALWAYS update these fields
        $updateData = [
            'price' => $data['price'] ?? $record->price,
            'stock' => $data['stock'] ?? $record->stock,
            'purchase_price' => $data['purchase_price'] ?? $record->purchase_price,
            'is_active' => $data['is_active'] ?? $record->is_active,
        ];
        
        if (!$hasVariations) {
            // Update simple product fields
            $updateData['stock'] = $data['stock'] ?? $record->stock;
            $updateData['on_sale'] = $data['on_sale'] ?? $record->on_sale;
            $updateData['sale_start'] = $data['sale_start'] ?? $record->sale_start;
            $updateData['sale_end'] = $data['sale_end'] ?? $record->sale_end;
            $updateData['has_variations'] = false;
            $updateData['variation_matrix'] = null;
            
            // Handle sale price
            if ($data['on_sale'] ?? false) {
                $updateData['sale_price'] = $data['sale_price'] ?? $record->sale_price;
            } else {
                $updateData['sale_price'] = null;
                $updateData['on_sale'] = false;
            }
        } else {
            // For variation products
            $updateData['has_variations'] = true;
            $updateData['variation_matrix'] = $data['variation_matrix'] ?? $record->variation_matrix;
            $updateData['stock'] = $data['stock'] ?? $record->stock; // Stock is per variation
            $updateData['on_sale'] = false; // Sale handled per variation
            $updateData['sale_price'] = null;
            $updateData['sale_start'] = null;
            $updateData['sale_end'] = null;
        }
        
        $record->update($updateData);
        return $record;
    }
}