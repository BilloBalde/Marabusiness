<x-mail::message>
# Votre devis n'a pas été retenu

Bonjour **{{ $vendor->store_name }}**,

L'acheteur n'a pas retenu votre devis pour la demande de prix **#{{ $rfq->id }}**
({{ $rfq->product->name ?? 'produit' }}).

## Le devis concerné

**Prix unitaire :** {{ number_format((float) $offer->unit_price, 2) }} {{ $offer->currency }}
**Quantité :** {{ number_format($offer->moq) }} unités
**Total :** {{ number_format($offer->total(), 2) }} {{ $offer->currency }}

@if($reason)
## Motif indiqué par l'acheteur

> {{ $reason }}
@endif

@if($canRequote)
La demande est de nouveau ouverte : vous pouvez envoyer un devis révisé.

<x-mail::button :url="url('/vendor/bulk-rfqs/' . $rfq->id . '/quote')">
Proposer un nouveau devis
</x-mail::button>
@else
Un autre devis est encore en lice sur cette demande.
@endif

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
