<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a vendor a buyer wants to negotiate the price of their basket.
 *
 * Synchronous like every other mailable here: no queue worker runs in production.
 */
class NegotiationOpened extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Négociation de prix — commande ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.negotiation.opened',
            with: [
                'order' => $this->order,
                'buyer' => $this->order->user,
                'vendor' => $this->order->vendor,
                'rfq' => $this->order->negotiation,
            ],
        );
    }
}
