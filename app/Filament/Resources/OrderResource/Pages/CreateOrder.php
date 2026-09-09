<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Paiement;
use Filament\Resources\Pages\CreateRecord;
use App\Mail\InvoiceMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return OrderResource::prepareOrderData($data);
    }

    protected function afterCreate(): void
    {
        /** @var \App\Models\Order $order */
        $order = $this->record;

        $data = $this->form->getState();

        // Parse initial payment amount from formatted input
        $rawAmount = $data['amount'] ?? 0;
        $amount    = (float) str_replace(',', '', (string) $rawAmount);

        if ($amount > 0) {
            $currencyCode = optional($order->vendor?->currency)->code ?? config('app.currency', 'USD');

            Paiement::create([
                'order_id'       => $order->id,
                'amount'         => $amount,
                'image'          => $data['image'] ?? null,
                'payment_method' => $data['payment_method'],
                'currency'       => $currencyCode,
                'payment_status' => $amount >= $order->grand_total ? 'paid' : 'partial',
                'transaction_id' => OrderResource::generateTransactionNumber(),
                // Entered from the back office by staff who have the money.
                'confirmed_at'   => now(),
                'confirmed_by'   => auth()->id(),
            ]);
        }

        // Update totals on order — from confirmed payments only. The one created
        // above is confirmed at creation (staff entered it, money in hand), but this
        // must still go through the same accounting as every other path: a manual
        // edit here that summed every payment regardless of confirmation is exactly
        // what let a buyer's unconfirmed declaration get banked as real money.
        $order->syncPaymentTotals();

        $pdf = Pdf::loadView('invoices.order', [
            'order' => $order,
            'payments' => $order->paiements,
        ])->setPaper('a4')->output();

        // Send email to customer
        Mail::to($order->user->email)->send(new InvoiceMail($order, $pdf));
    }
}
