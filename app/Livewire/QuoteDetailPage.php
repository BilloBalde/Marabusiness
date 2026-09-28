<?php

namespace App\Livewire;

use App\Models\Quote;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Mon devis - MARA BUSINESS')]
class QuoteDetailPage extends Component
{
    use WithFileUploads;

    public $quote;

    public $reply = '';
    public $attachments = [];

    protected function rules(): array
    {
        return [
            'reply' => 'required|string|min:2|max:2000',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:10240',
        ];
    }

    public function mount(int $quote): void
    {
        $this->quote = Quote::where('client_id', Auth::id())
            ->with(['messages.attachments', 'sourceProduct.sourcing'])
            ->findOrFail($quote);
    }

    public function sendReply(): void
    {
        $this->validate();

        $paths = collect($this->attachments)
            ->filter()
            ->map(fn ($file) => $file->store('quotes', 'public_uploads'))
            ->values()
            ->all();

        $this->quote->postMessage(Auth::user(), $this->reply, $paths);

        $this->reply = '';
        $this->attachments = [];
        $this->quote->refresh()->load('messages.attachments');
    }

    public function render()
    {
        return view('livewire.quote-detail-page');
    }
}
