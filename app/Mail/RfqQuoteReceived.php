<?php

namespace App\Mail;

use App\Models\BulkRfqOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a buyer a vendor has answered their request for quotation.
 *
 * Without it the buyer had to think to reopen the conversation to discover a quote had
 * arrived — nothing reached them outside the site.
 *
 * Synchronous like every other mailable here: no queue worker runs in production, so a
 * queued mail would never leave the jobs table.
 */
class RfqQuoteReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BulkRfqOffer $offer)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vous avez reçu un devis — demande #' . $this->offer->bulk_rfq_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.rfq.quote-received',
            with: [
                'offer'  => $this->offer,
                'rfq'    => $this->offer->rfq,
                'vendor' => $this->offer->vendor,
                'buyer'  => $this->offer->rfq->user,
            ],
        );
    }
}
