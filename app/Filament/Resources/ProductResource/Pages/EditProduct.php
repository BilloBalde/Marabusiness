<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\VendorProduct;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
    protected static function isVendorPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'vendor';
    }

    protected function authorizeAccess(): void
    {
        $user = Filament::auth()->user();
        $record = $this->getRecord();
        
        // Admin can access everything (not in vendor panel)
        if (!static::isVendorPanel()) {
            return;
        }
        
        // Vendor can access if they created the entry or actually list it — the
        // same combined check as ProductResource::getEloquentQuery() and the Edit
        // action's visibility, via ProductResource::ownedByVendor(). Checking
        // created_by alone here (while the list already allowed either) is what
        // let a vendor see a product in "Produits" and then get bounced right back
        // out trying to open it.
        if (! ProductResource::ownedByVendor($record, $user->id, $this->vendorId())) {
            Notification::make()
                ->title('Access Denied')
                ->body('You are not authorized to edit this product. You can only edit products that you created.')
                ->danger()
                ->persistent()
                ->send();
            
            // Redirect back to index
            $this->redirect(ProductResource::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        if (!static::isVendorPanel()) {
            return [
                Actions\DeleteAction::make(),
            ];
        }else {
            return [];
        }
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
        $record = $this->record;

        $record->syncTranslations([
            'en' => [
                'name' => $this->data['name_en'] ?? null,
                'short_description' => $this->data['short_description_en'] ?? null,
                'description' => $this->data['description_en'] ?? null,
            ],
            'fr' => [
                'name' => $this->data['name_fr'] ?? null,
                'short_description' => $this->data['short_description_fr'] ?? null,
                'description' => $this->data['description_fr'] ?? null,
            ],
            'zh' => [
                'name' => $this->data['name_zh'] ?? null,
                'short_description' => $this->data['short_description_zh'] ?? null,
                'description' => $this->data['description_zh'] ?? null,
            ],
        ]);
        
        // ADMIN PANEL → stop here
        if (! $this->isVendorPanel()) {
            return;
        }

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
        $record = $this->record;

        $data['name_en'] = $record->getTranslation('name', 'en');
        $data['name_fr'] = $record->getTranslation('name', 'fr');
        $data['name_zh'] = $record->getTranslation('name', 'zh');

        $data['short_description_en'] = $record->getTranslation('short_description', 'en');
        $data['short_description_fr'] = $record->getTranslation('short_description', 'fr');
        $data['short_description_zh'] = $record->getTranslation('short_description', 'zh');

        $data['description_en'] = $record->getTranslation('description', 'en');
        $data['description_fr'] = $record->getTranslation('description', 'fr');
        $data['description_zh'] = $record->getTranslation('description', 'zh');

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
