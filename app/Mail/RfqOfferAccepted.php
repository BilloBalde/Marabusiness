<?php

namespace App\Mail;

use App\Models\BulkRfqOffer;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a vendor their quote was accepted and an order now exists.
 *
 * Sent synchronously, like every other mailable in this application: no queue worker
 * runs alongside the web service, so a queued mail would sit in the jobs table
 * indefinitely instead of reaching the vendor.
 */
class RfqOfferAccepted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BulkRfqOffer $offer,
        public Order $order,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Devis accepté — commande ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.rfq.offer-accepted',
            with: [
                'offer'  => $this->offer,
                'order'  => $this->order,
                'rfq'    => $this->offer->rfq,
                'vendor' => $this->order->vendor,
                'buyer'  => $this->order->user,
            ],
        );
    }
}
