<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\VendorProduct;
use Filament\Facades\Filament;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Vendor Panel detection
     */
    protected function isVendorPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'vendor';
    }

    protected function vendorId(): ?int
    {
        $user = Filament::auth()->user();
        return $user?->vendor->id ?? $user?->vendor_id ?? null;
    }

    /**
     * Save pivot fields AFTER saving the product itself
     */
    protected function afterSave(): void
    {
        // ADMIN PANEL → stop here
        if (! $this->isVendorPanel()) {
            return;
        }

        $record = $this->record;
        $vendorId = $this->vendorId();
        $productId = $this->record->id;

        // Retrieve form data
        $data = $this->form->getState();

        $price  = $data['pivot_price'] ?? null;
        $onSale = $data['pivot_on_sale'] ?? false;
        $salePrice = $onSale ? ($data['pivot_sale_price'] ?? null) : null;

        $discount = null;
        if ($onSale && $salePrice && $salePrice < $price) {
            $discount = round(100 - ($salePrice / $price * 100));
        }

        VendorProduct::updateOrCreate(
            [
                'vendor_id'  => $vendorId,
                'product_id' => $productId,
            ],
            [
                'price'            => $price,
                'sale_price'       => $salePrice,
                'discount_percent' => $discount,
                'sale_start'       => $data['pivot_sale_start'] ?? null,
                'sale_end'         => $data['pivot_sale_end'] ?? null,
            ]
        );

        if ($onSale) {
            $record->on_sale    = true;
        } else {
            $record->on_sale    = false;
        }

        $record->save();
    }

    /**
     * Hydrate vendor pivot values when editing
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! $this->isVendorPanel()) {
            return $data;
        }

        $vendorId = $this->vendorId();
        $productId = $this->record->id;

        $pivot = VendorProduct::where('vendor_id', $vendorId)
            ->where('product_id', $productId)
            ->first();

        if ($pivot) {
            $data['pivot_price']       = $pivot->price;
            $data['pivot_on_sale']     = $pivot->sale_price !== null;
            $data['pivot_sale_price']  = $pivot->sale_price;
            $data['pivot_sale_start']  = $pivot->sale_start;
            $data['pivot_sale_end']    = $pivot->sale_end;
        }

        return $data;
    }

    /**
     * Prevent product fields being overwritten in vendor panel
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! $this->isVendorPanel()) {
            return $data;
        }

        // Vendor may NOT change product core data
        unset($data['name'], $data['slug'], $data['category_id'], $data['brand_id'], $data['images']);

        return $data;
    }
}
