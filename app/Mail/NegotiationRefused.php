<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the vendor the buyer turned their price down. The discussion reopens
 * rather than ending, so the vendor can name another one.
 *
 * Synchronous like every other mailable here: no queue worker runs in production.
 */
class NegotiationRefused extends Mailable
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
            subject: 'Prix refusé — commande ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.negotiation.refused',
            with: [
                'order' => $this->order,
                'vendor' => $this->order->vendor,
                'buyer' => $this->order->user,
                'reason' => $this->reason,
            ],
        );
    }
}
