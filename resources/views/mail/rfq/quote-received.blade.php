<x-mail::message>
# Vous avez reçu un devis

Bonjour **{{ $buyer->name }}**,

La boutique **{{ $vendor->store_name }}** a répondu à votre demande de prix
**#{{ $rfq->id }}** ({{ $rfq->product->name ?? 'produit' }}).

## Le devis proposé

**Prix unitaire :** {{ number_format((float) $offer->unit_price, 2) }} {{ $offer->currency }}
**Quantité minimale :** {{ number_format($offer->moq) }} unités
**Livraison :** {{ number_format((float) $offer->shipping_cost, 2) }} {{ $offer->currency }} ({{ $offer->shipping_terms }})
**Délai de préparation :** {{ $offer->lead_time_days }} jours
**Total :** {{ number_format($offer->total(), 2) }} {{ $offer->currency }}

@if($offer->vendor_notes)
## Message du vendeur

> {{ $offer->vendor_notes }}
@endif

Vous pouvez accepter ce devis — une commande sera créée au prix négocié — ou le refuser
en indiquant vos raisons, afin que le vendeur vous en propose un autre.

<x-mail::button :url="route('rfq.chat', $rfq->id)">
Voir le devis et répondre
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
