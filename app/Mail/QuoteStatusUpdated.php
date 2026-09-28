<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the client their quote's status changed (processing/validated/cancelled).
 *
 * Synchronous like every other mailable here: no queue worker runs in production.
 */
class QuoteStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    private const SUBJECTS = [
        'processing' => 'Votre devis est en cours de traitement',
        'validated' => 'Votre devis a été validé',
        'cancelled' => 'Votre devis a été annulé',
    ];

    public function __construct(public Quote $quote)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: self::SUBJECTS[$this->quote->status] ?? 'Votre devis a été mis à jour',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quotes.status-updated',
            with: [
                'quote' => $this->quote,
                'client' => $this->quote->client,
                'sourceProduct' => $this->quote->sourceProduct,
            ],
        );
    }
}
