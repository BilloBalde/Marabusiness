<x-mail::message>
# Négociation annulée

**{{ $buyer->name }}** a annulé sa demande de négociation pour la commande
**{{ $order->order_number }}** ({{ $vendor->store_name }}).

@if($reason)
## Raison indiquée

> {{ $reason }}
@endif

La commande a été fermée — aucune action n'est nécessaire de votre part.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
