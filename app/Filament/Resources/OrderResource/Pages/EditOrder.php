<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\VendorProduct;
use App\Models\VendorProductVariation;
use Illuminate\Support\Facades\Log;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Keep old items to adjust stock properly.
     */
    protected ?array $oldItems = null;

    public function mount($record): void
    {
        parent::mount($record);

        // Capture old items BEFORE editing
        $this->oldItems = $this->record
            ->items()
            ->get()
            ->map(fn ($i) => [
                'product_id' => $i->product_id,
                'quantity'   => $i->quantity,
                'unit_amount' => $i->unit_amount,
                'variation_json' => $i->variation_json,
            ])
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make()
                ->visible(fn () => $this->record->payment_status !== 'paid'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return OrderResource::prepareOrderData($data, $this->record);
    }

    protected function afterSave(): void
    {
        /** @var \App\Models\Order $order */
        $order = $this->record->fresh();

        // New items after save
        $newItems = $order->items()
            ->get()
            ->map(fn ($i) => [
                'product_id' => $i->product_id,
                'quantity'   => $i->quantity,
                'unit_amount' => $i->unit_amount,
                'variation_json' => $i->variation_json,
            ])
            ->toArray();

        $this->updateStock($order, $newItems);
        
        // Update order totals after stock adjustment
        $this->recalculateOrderTotals($order);
    }

    /**
     * Adjust vendor stock using diff between old and new items.
     */
    protected function updateStock(Model $order, array $newItems): void
    {
        $vendorId = $order->vendor_id;

        $old = collect($this->oldItems ?? []);
        $new = collect($newItems);

        // Handle stock updates for each item
        foreach ($new as $newItem) {
            $productId = $newItem['product_id'];
            $qtyNew = (int) $newItem['quantity'];
            
            // Find matching old item
            $oldItem = $old->firstWhere('product_id', $productId);
            
            if ($oldItem) {
                // Item exists, calculate difference
                $qtyOld = (int) $oldItem['quantity'];
                $diff = $qtyNew - $qtyOld;
                
                if ($diff !== 0) {
                    $this->adjustStock($vendorId, $productId, $newItem['variation_json'], -$diff);
                    // Note: diff is positive if quantity increased, so we need to decrement stock by diff
                }
            } else {
                // New item added → decrement stock
                $this->adjustStock($vendorId, $productId, $newItem['variation_json'], -$qtyNew);
            }
        }

        // Restore stock for deleted items
        foreach ($old as $oldItem) {
            $productId = $oldItem['product_id'];
            
            // Check if item was removed
            $stillExists = $new->contains(function ($item) use ($productId) {
                return $item['product_id'] == $productId;
            });
            
            if (!$stillExists) {
                // Item was removed → restore stock (add back the old quantity)
                $this->adjustStock($vendorId, $productId, $oldItem['variation_json'], $oldItem['quantity']);
            }
        }
    }

    /**
     * Adjust stock for vendor product (with or without variations).
     */
    protected function adjustStock(int $vendorId, int $productId, ?string $variationJson, int $quantityDiff): void
    {
        try {
            $vendorProduct = VendorProduct::where('vendor_id', $vendorId)
                ->where('product_id', $productId)
                ->first();
            
            if (!$vendorProduct) {
                Log::warning("VendorProduct not found", [
                    'vendor_id' => $vendorId,
                    'product_id' => $productId
                ]);
                return;
            }
            
            if ($vendorProduct->has_variations && $variationJson) {
                try {
                    $variations = json_decode($variationJson, true);
                    if ($variations && is_array($variations)) {
                        // Find the matching variation
                        $variation = VendorProductVariation::where('vendor_product_id', $vendorProduct->id)
                            ->whereJsonContains('attributes', $variations)
                            ->first();
                        
                        if ($variation) {
                            $variation->stock += $quantityDiff;
                            if ($variation->stock < 0) {
                                Log::warning("Variation stock would go negative", [
                                    'variation_id' => $variation->id,
                                    'current_stock' => $variation->stock - $quantityDiff,
                                    'adjustment' => $quantityDiff
                                ]);
                                $variation->stock = 0;
                            }
                            $variation->save();
                            
                            // Update main vendor product stock (sum of all variations)
                            $totalStock = VendorProductVariation::where('vendor_product_id', $vendorProduct->id)
                                ->sum('stock');
                            $vendorProduct->stock = $totalStock;
                            $vendorProduct->save();
                        } else {
                            // Variation not found, update main product stock
                            Log::warning("Variation not found, updating main product", [
                                'vendor_product_id' => $vendorProduct->id,
                                'attributes' => $variations
                            ]);
                            $vendorProduct->stock += $quantityDiff;
                            if ($vendorProduct->stock < 0) {
                                $vendorProduct->stock = 0;
                            }
                            $vendorProduct->save();
                        }
                    }
                } catch (\Exception $e) {
                    // If variation matching fails, fall back to main product stock
                    Log::error("Error updating variation stock", [
                        'vendor_product_id' => $vendorProduct->id,
                        'error' => $e->getMessage()
                    ]);
                    $vendorProduct->stock += $quantityDiff;
                    if ($vendorProduct->stock < 0) {
                        $vendorProduct->stock = 0;
                    }
                    $vendorProduct->save();
                }
            } else {
                // No variations, update main product stock
                $vendorProduct->stock += $quantityDiff;
                if ($vendorProduct->stock < 0) {
                    Log::warning("Product stock would go negative", [
                        'vendor_product_id' => $vendorProduct->id,
                        'current_stock' => $vendorProduct->stock - $quantityDiff,
                        'adjustment' => $quantityDiff
                    ]);
                    $vendorProduct->stock = 0;
                }
                $vendorProduct->save();
            }
        } catch (\Exception $e) {
            Log::error("Error in adjustStock", [
                'vendor_id' => $vendorId,
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Recalculate order totals after editing.
     */
    protected function recalculateOrderTotals(Model $order): void
    {
        $order->refresh();

        // Recalculate grand total from items + shipping
        $itemsTotal = $order->items()->sum('total_amount');
        $shippingAmount = $order->shipping_amount ?? 0;
        $grandTotal = $itemsTotal + $shippingAmount;
        $order->update(['grand_total' => $grandTotal]);

        // Balance is recomputed from confirmed payments only, against the fresh
        // grand_total above — summing every payment regardless of confirmation is
        // what let a buyer's own unconfirmed declaration get banked as real money.
        $order->syncPaymentTotals();
    }
}