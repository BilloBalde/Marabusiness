<?php

namespace App\Filament\Resources\PaiementResource\Pages;

use App\Filament\Resources\PaiementResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaiement extends CreateRecord
{
    protected static string $resource = PaiementResource::class;

    /**
     * Entered from the back office by staff who have the money or the proof in
     * hand — same as an initial payment typed into the order form (CreateOrder) or
     * taken at the counter (PointOfSale). Without this, Paiement::booted() would
     * read it as an unconfirmed buyer declaration and email the vendor to confirm
     * a payment the vendor's own admin just recorded.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['confirmed_at'] = now();
        $data['confirmed_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $order = Order::find($data['order_id']);

        // Balance is recomputed from confirmed payments only — see
        // PaiementResource::updateOrderTotals().
        PaiementResource::updateOrderTotals($order);
    }
}
