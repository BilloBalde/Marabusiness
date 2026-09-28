<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the vendor the buyer gave up on the negotiation. Nothing was paid or
 * booked, so the order simply closes.
 *
 * Synchronous like every other mailable here: no queue worker runs in production.
 */
class NegotiationCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $reason = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Négociation annulée — commande ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.negotiation.cancelled',
            with: [
                'order' => $this->order,
                'vendor' => $this->order->vendor,
                'buyer' => $this->order->user,
                'reason' => $this->reason,
            ],
        );
    }
}
