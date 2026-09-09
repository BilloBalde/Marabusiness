<x-mail::message>
# Votre devis a été accepté

Bonjour **{{ $vendor->store_name }}**,

{{ $buyer->name }} a accepté votre devis pour la demande de prix **#{{ $rfq->id }}**
({{ $rfq->product->name ?? 'produit' }}). Une commande a été créée automatiquement.

## Conditions retenues

**Prix unitaire :** {{ number_format((float) $offer->unit_price, 2) }} {{ $offer->currency }}
**Quantité :** {{ number_format($offer->moq) }} unités
**Livraison :** {{ number_format((float) $offer->shipping_cost, 2) }} {{ $offer->currency }} ({{ $offer->shipping_terms }})
**Délai annoncé :** {{ $offer->lead_time_days }} jours
**Total du devis :** {{ number_format($offer->total(), 2) }} {{ $offer->currency }}

## Commande créée

**Numéro :** {{ $order->order_number }}
**Montant :** {{ number_format((float) $order->grand_total, 2) }} {{ $vendor->currency->code ?? '' }}
**Statut du paiement :** en attente de règlement par l'acheteur

Le montant ci-dessus est converti dans la devise de votre boutique ; le devis reste
contractuellement celui que vous avez envoyé.

<x-mail::button :url="url('/vendor/orders')">
Voir la commande
</x-mail::button>

Préparez l'expédition une fois le paiement confirmé.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
