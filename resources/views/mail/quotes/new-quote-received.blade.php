<x-mail::message>
# Nouvelle demande de devis

**{{ $client->name }}** vient de demander un devis pour **{{ $sourceProduct->name_text }}**
({{ $sourcing->title_text }}).

@if($quote->moq)
**Quantité souhaitée (MOQ) :** {{ number_format($quote->moq) }}
@endif

## Message du client

> {{ $quote->message }}

<x-mail::button :url="\App\Filament\Resources\QuoteResource::getUrl('view', ['record' => $quote])">
Voir le devis et répondre
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
