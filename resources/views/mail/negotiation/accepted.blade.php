<x-mail::message>
# Prix accepté

**{{ $buyer->name }}** a accepté votre prix pour la commande **{{ $order->order_number }}**.

## Commande

**Montant convenu :** {{ number_format((float) $order->grand_total, 2) }} {{ $vendor->currency->code ?? '' }}
**Statut du paiement :** en attente de règlement par le client

<x-mail::button :url="\App\Filament\Vendor\Resources\OrderResource::getUrl('view', ['record' => $order])">
Voir la commande
</x-mail::button>

Préparez l'expédition une fois le paiement confirmé.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
