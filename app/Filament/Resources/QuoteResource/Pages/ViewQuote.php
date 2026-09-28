<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Mail\QuoteStatusUpdated;
use App\Models\Quote;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\WithFileUploads;

class ViewQuote extends ViewRecord
{
    use WithFileUploads;

    protected static string $resource = QuoteResource::class;

    protected static string $view = 'filament.resources.quote-resource.pages.view-quote';

    public string $reply = '';

    public array $attachments = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('markProcessing')
                ->label('Marquer en traitement')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === 'pending')
                ->action(fn () => $this->updateStatus('processing')),

            Actions\Action::make('markValidated')
                ->label('Marquer validé')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'processing']))
                ->action(fn () => $this->updateStatus('validated')),

            Actions\Action::make('markCancelled')
                ->label('Annuler le devis')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== 'cancelled')
                ->action(fn () => $this->updateStatus('cancelled')),
        ];
    }

    /**
     * Sent after the status is already saved: a mail failure must never roll
     * back or block a status change the admin already sees confirmed on screen.
     */
    private function updateStatus(string $status): void
    {
        $this->record->update(['status' => $status]);

        $client = $this->record->client;

        if (! $client?->email) {
            Log::warning("Devis #{$this->record->id} : le client n'a pas d'e-mail, notification non envoyée.");

            return;
        }

        try {
            Mail::to($client->email)->send(new QuoteStatusUpdated($this->record->fresh(['client', 'sourceProduct'])));
        } catch (\Throwable $e) {
            Log::error("Échec de la notification de changement de statut au client.", [
                'quote_id' => $this->record->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Devis')
                    ->schema([
                        Infolists\Components\TextEntry::make('client.name')
                            ->label('Client'),
                        Infolists\Components\TextEntry::make('client.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('sourceProduct.name.fr')
                            ->label('Produit'),
                        Infolists\Components\TextEntry::make('sourceProduct.sourcing.title.fr')
                            ->label('Sourcing'),
                        Infolists\Components\TextEntry::make('moq')
                            ->label('MOQ')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'validated' => 'success',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('message')
                            ->label('Message initial')
                            ->columnSpanFull()
                            ->prose(),
                    ])->columns(2),
            ]);
    }

    protected function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'min:2', 'max:2000'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:10240'],
        ];
    }

    public function sendReply(): void
    {
        $this->validate();

        $paths = collect($this->attachments)
            ->filter()
            ->map(fn ($file) => $file->store('quotes', 'public_uploads'))
            ->values()
            ->all();

        $this->record->postMessage(Auth::user(), $this->reply, $paths);

        $this->reply = '';
        $this->attachments = [];
        $this->record->refresh()->load('messages.attachments');
    }
}
