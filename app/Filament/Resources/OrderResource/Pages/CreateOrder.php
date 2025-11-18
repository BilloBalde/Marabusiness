<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Paiement;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        //dd($data);
        $order = $this->form->getRecord();

        $amount = $data['payment_method'] === 'om' ? floatval(str_replace(',', '', $data['amount'])) : 0;

        $payment = new Paiement([
            'order_id' => $order->id,
            'amount' => $amount,
            'image' => $data['payment_method'] === 'om' ? $data['image'] : 'payments/default.png',
            'payment_method' => $data['payment_method'],
            'currency' => $data['currency'],
            'payment_status' => $amount == $order->grand_total ? 'paid' : 'partial',
            'transaction_id' => $data['transaction_id'], // Ensure transaction_id is set if available
        ]);
        $payment->save();


        // Update order with totals
        $totalPaid = $order->paiements()->sum('amount');
        $remaining = max(0, $order->grand_total - $totalPaid);

        $order->update([
            'total_paid' => $totalPaid,
            'total_remaining' => $remaining,
        ]);
    }

}
