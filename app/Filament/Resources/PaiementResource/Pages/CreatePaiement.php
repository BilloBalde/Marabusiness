<?php

namespace App\Filament\Resources\PaiementResource\Pages;

use App\Filament\Resources\PaiementResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaiement extends CreateRecord
{
    protected static string $resource = PaiementResource::class;
    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $order = Order::find($data['order_id']);
        // Update order with totals
        $totalPaid = $order->paiements()->sum('amount');
        $remaining = max(0, $order->grand_total - $totalPaid);

        $order->update([
            'total_paid' => $totalPaid,
            'total_remaining' => $remaining,
        ]);
    }
}
