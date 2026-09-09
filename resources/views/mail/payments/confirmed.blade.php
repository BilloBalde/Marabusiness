<x-mail::message>
# Paiement Confirmé !

Bonjour {{ $order->user->name ?? 'Client' }},

Nous avons le plaisir de vous confirmer que votre paiement pour la commande **#{{ $order->id }}** a été traité avec succès.

<?php $currency = $order->vendor->currency->code ?? 'USD'; ?>

<x-mail::panel>
**Détails de la commande:**
- Référence: #{{ $order->order_number ?? $order->id }}
- Date: {{ $order->created_at->format('d/m/Y') }}
- Montant total: {{ number_format($order->grand_total, 2, ',', ' ') }} {{ $currency }}
- Statut: Paiement confirmé
</x-mail::panel>

@if($payment)
<x-mail::panel>
**Détails du paiement:**
- Méthode: {{ $payment->payment_method ?? 'Non spécifié' }}
- Référence: {{ $payment->transaction_id ?? 'N/A' }}
- Montant: {{ number_format($payment->amount, 2, ',', ' ') }} {{ $payment->currency ?? $currency }}
- Date: {{ $payment->created_at->format('d/m/Y H:i') }}
</x-mail::panel>
@endif

Votre commande est maintenant en cours de traitement. Nous vous tiendrons informé de son évolution.

<x-mail::button :url="$url">
Voir ma commande
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>