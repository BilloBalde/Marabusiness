<?php

namespace App\Livewire;

use App\Mail\NewSourcingQuoteReceived;
use App\Models\Quote;
use App\Models\SourceProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Demande de devis - MARA BUSINESS')]
class SourcingQuotePage extends Component
{
    use WithFileUploads;

    public $sourceProduct;

    public $moq;
    public $message = '';
    public $attachments = [];

    protected function rules(): array
    {
        return [
            'moq' => 'nullable|integer|min:1',
            'message' => 'required|string|min:5|max:2000',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:10240',
        ];
    }

    public function mount(string $sourcing, string $sourceProduct): void
    {
        $this->sourceProduct = SourceProduct::where('slug', $sourceProduct)
            ->whereHas('sourcing', fn ($q) => $q->where('slug', $sourcing))
            ->firstOrFail();

        $this->moq = $this->sourceProduct->quantity_minimum;
    }

    public function submit()
    {
        $this->validate();

        $paths = collect($this->attachments)
            ->filter()
            ->map(fn ($file) => $file->store('quotes', 'public_uploads'))
            ->values()
            ->all();

        $quote = Quote::create([
            'client_id' => Auth::id(),
            'type' => 'sourcing',
            'source_product_id' => $this->sourceProduct->id,
            'moq' => $this->moq,
            'message' => $this->message,
            'attachments' => $paths,
            'status' => 'pending',
        ]);

        // Opens the thread the admin actually sees — the quote row alone
        // isn't visible to them as a conversation, only as a record.
        $opening = "Nouvelle demande de devis — {$this->sourceProduct->name_text}"
            . ($this->moq ? " (MOQ {$this->moq})" : '')
            . ".\n\n{$this->message}";

        $quote->postMessage(Auth::user(), $opening, $paths);

        $this->notifyAdmin($quote);

        session()->flash('success', 'Votre demande de devis a été envoyée. Nous vous répondrons rapidement.');

        return redirect()->route('sourcing.show', $this->sourceProduct->sourcing->slug);
    }

    /**
     * Sent after the quote and its opening message are already saved: a mail
     * failure must never roll back or block a request the client already sees
     * confirmed on screen.
     */
    private function notifyAdmin(Quote $quote): void
    {
        $admin = Quote::adminRecipient();

        if (! $admin?->email) {
            Log::warning("Devis #{$quote->id} : aucun admin avec e-mail, notification non envoyée.");

            return;
        }

        try {
            Mail::to($admin->email)->send(new NewSourcingQuoteReceived($quote->fresh(['client', 'sourceProduct.sourcing'])));
        } catch (\Throwable $e) {
            Log::error("Échec de la notification de nouveau devis à l'admin.", [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.sourcing-quote-page');
    }
}
