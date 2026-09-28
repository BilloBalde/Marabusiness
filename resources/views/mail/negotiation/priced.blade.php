<x-mail::message>
# Un prix vous a été proposé

Bonjour **{{ $buyer->name }}**,

**{{ $vendor->store_name }}** a répondu à votre demande de négociation pour la commande
**{{ $order->order_number }}**.

## Le prix proposé

**Total :** {{ number_format((float) $order->negotiated_total, 2) }} {{ $vendor->currency->code ?? '' }}
**Valable jusqu'au :** {{ $order->negotiated_expires_at?->format('d/m/Y') }}

Vous pouvez accepter ce prix — la commande devient alors réglable — ou le refuser pour
poursuivre la discussion.

<x-mail::button :url="route('user.rfqs')">
Voir et répondre
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
