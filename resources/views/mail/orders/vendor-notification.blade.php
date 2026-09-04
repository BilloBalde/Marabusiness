<x-mail::message>
# Nouvelle commande reçue !

Bonjour **{{ $vendor->store_name }}**,

Vous avez reçu une nouvelle commande de la part de **{{ $customer->first_name ?? $customer->name }} {{ $customer->last_name ?? '' }}**.

## Détails de la commande

**Numéro de commande :** {{ $order->order_number }}
**Date :** {{ $order->created_at->format('d/m/Y H:i') }}
**Mode de paiement :** {{ $order->payment_method == 'cod' ? 'Paiement à la livraison' : ($order->payment_method == 'stripe' ? 'Carte bancaire' : ($order->payment_method == 'om' ? 'Orange Money' : 'Espèces')) }}
**Statut de paiement :** {{ ucfirst($order->payment_status) }}

## Articles commandés

| Produit | Quantité | Prix unitaire | Total |
|---------|----------|---------------|-------|
@foreach($items as $item)
| {{ $item->product->name ?? 'Produit' }} | {{ $item->quantity }} | {{ number_format($item->unit_amount, 2) }} {{ $order->vendor->currency->code ?? 'GNF' }} | {{ number_format($item->total_amount, 2) }} {{ $order->vendor->currency->code ?? 'GNF' }} |
@endforeach

## Informations de livraison

**Destinataire :** {{ $order->address->first_name }} {{ $order->address->last_name }}
**Téléphone :** {{ $order->address->phone }}
**Adresse :** {{ $order->address->street_address }}, {{ $order->address->city }}, {{ $order->address->state }}, {{ $order->address->zip_code }}, {{ $order->address->country }}
@if($order->address->zone)
**Zone de livraison :** {{ $order->address->zone }}
@endif

## Récapitulatif financier

- **Sous-total :** {{ number_format($order->grand_total - $order->shipping_amount, 2) }} {{ $order->vendor->currency->code ?? 'GNF' }}
- **Frais de livraison :** {{ number_format($order->shipping_amount, 2) }} {{ $order->vendor->currency->code ?? 'GNF' }}
- **Total :** {{ number_format($order->grand_total, 2) }} {{ $order->vendor->currency->code ?? 'GNF' }}

<x-mail::button :url="route('vendor.orders.show', $order)">
Gérer la commande
</x-mail::button>

---

**Instructions importantes :**
- Préparez la commande dès que possible
- Si vous utilisez un service de livraison, confirmez l'expédition depuis votre tableau de bord
- En cas de question, contactez le client au {{ $order->address->phone }}

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>