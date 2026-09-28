<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the vendor the buyer accepted their price. The order is now an
 * ordinary, payable order.
 *
 * Synchronous like every other mailable here: no queue worker runs in production.
 */
class NegotiationAccepted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Prix accepté — commande ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.negotiation.accepted',
            with: [
                'order' => $this->order,
                'vendor' => $this->order->vendor,
                'buyer' => $this->order->user,
            ],
        );
    }
}
