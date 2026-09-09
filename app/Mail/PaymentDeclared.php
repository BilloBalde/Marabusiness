<?php

namespace App\Mail;

use App\Models\Paiement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a vendor a buyer says they have paid, and that the order is waiting on them.
 *
 * Buyers' declarations no longer settle an order by themselves, so without this the
 * money would sit unconfirmed until the vendor happened to open the order.
 *
 * Synchronous like every other mailable here: no queue worker runs in production.
 */
class PaymentDeclared extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Paiement $paiement)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paiement à confirmer — commande ' . ($this->paiement->order->order_number ?? ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.payments.declared',
            with: [
                'paiement' => $this->paiement,
                'order'    => $this->paiement->order,
                'buyer'    => $this->paiement->order?->user,
                'vendor'   => $this->paiement->order?->vendor,
            ],
        );
    }
}
