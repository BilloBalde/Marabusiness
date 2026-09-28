<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the admin a client just submitted a sourcing quote request.
 *
 * Without it, a new quote was only visible by remembering to open Sourcing > Devis.
 * Synchronous like every other mailable here: no queue worker runs in production.
 */
class NewSourcingQuoteReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle demande de devis — ' . $this->quote->sourceProduct->name_text,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quotes.new-quote-received',
            with: [
                'quote' => $this->quote,
                'client' => $this->quote->client,
                'sourceProduct' => $this->quote->sourceProduct,
                'sourcing' => $this->quote->sourceProduct->sourcing,
            ],
        );
    }
}
