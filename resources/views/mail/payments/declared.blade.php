<x-mail::message>
# Un paiement attend votre confirmation

Bonjour **{{ $vendor->store_name ?? '' }}**,

{{ $buyer->name ?? 'Un client' }} déclare avoir réglé la commande
**{{ $order->order_number ?? '' }}**. Tant que vous ne confirmez pas avoir reçu cet
argent, la commande reste marquée comme non payée.

## Ce qui est déclaré

**Montant :** {{ number_format((float) $paiement->amount, 2) }} {{ $paiement->currency }}
**Moyen :** {{ $paiement->payment_method === 'cod' ? 'Espèces à la livraison' : ($paiement->payment_method === 'om' ? 'Orange Money' : $paiement->payment_method) }}
**Référence :** {{ $paiement->transaction_id }}
**Déclaré le :** {{ $paiement->created_at->format('d/m/Y à H:i') }}

@if($paiement->payment_method === 'cod')
Confirmez une fois que le livreur vous a remis les espèces.
@elseif($paiement->image)
Le client a joint un justificatif de son transfert : vérifiez-le avant de confirmer.
@else
Aucun justificatif n'a été joint.
@endif

<x-mail::button :url="url('/vendor/orders/' . ($order->id ?? ''))">
Vérifier et confirmer
</x-mail::button>

Le solde de la commande sera mis à jour dès votre confirmation.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
