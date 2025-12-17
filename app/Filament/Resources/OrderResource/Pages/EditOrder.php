<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
            ])
            ->keyBy('product_id')
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
        $order = $this->record;

        // New items after save
        $newItems = $order->items()
            ->get()
            ->map(fn ($i) => [
                'product_id' => $i->product_id,
                'quantity'   => $i->quantity,
            ])
            ->toArray();

        $this->updateStock($order, $newItems);
        $this->recalculatePayments($order);
    }

    /**
     * Adjust vendor stock using diff between old and new items.
     */
    protected function updateStock(Model $order, array $newItems): void
    {
        $vendorId = $order->vendor_id;

        $old = collect($this->oldItems ?? []);
        $new = collect($newItems)->keyBy('product_id');

        // Update existing / new rows
        foreach ($new as $productId => $item) {
            $qtyNew = (int) $item['quantity'];

            if ($old->has($productId)) {
                $qtyOld = (int) $old[$productId]['quantity'];
                $diff   = $qtyNew - $qtyOld;

                if ($diff !== 0) {
                    DB::table('vendor_product')
                        ->where('vendor_id', $vendorId)
                        ->where('product_id', $productId)
                        ->decrement('stock', $diff);
                }
            } else {
                // New product added → decrement stock
                DB::table('vendor_product')
                    ->where('vendor_id', $vendorId)
                    ->where('product_id', $productId)
                    ->decrement('stock', $qtyNew);
            }
        }

        // Restore stock for deleted rows
        foreach ($old as $productId => $item) {
            if (! $new->has($productId)) {
                DB::table('vendor_product')
                    ->where('vendor_id', $vendorId)
                    ->where('product_id', $productId)
                    ->increment('stock', (int) $item['quantity']);
            }
        }
    }

    /**
     * Recalculate paid / remaining & payment_status.
     */
    protected function recalculatePayments(Model $order): void
    {
        $totalPaid = $order->paiements()->sum('amount');
        $remaining = max(0, $order->grand_total - $totalPaid);

        $order->update([
            'total_paid'      => $totalPaid,
            'total_remaining' => $remaining,
            'payment_status'  => $remaining <= 0
                ? 'paid'
                : ($totalPaid > 0 ? 'partial' : 'pending'),
        ]);
    }
}
