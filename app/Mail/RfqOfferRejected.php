<?php

namespace App\Mail;

use App\Models\BulkRfqOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a vendor their quote was turned down, with the buyer's reason when they gave
 * one — the point being that the vendor can send a revised quote, since the request
 * reopens when no other quote is left standing.
 *
 * Synchronous for the same reason as RfqOfferAccepted.
 */
class RfqOfferRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BulkRfqOffer $offer,
        public ?string $reason = null,
        public bool $canRequote = false,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Devis refusé — demande de prix #' . $this->offer->bulk_rfq_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.rfq.offer-rejected',
            with: [
                'offer'      => $this->offer,
                'rfq'        => $this->offer->rfq,
                'vendor'     => $this->offer->vendor,
                'reason'     => $this->reason,
                'canRequote' => $this->canRequote,
            ],
        );
    }
}
