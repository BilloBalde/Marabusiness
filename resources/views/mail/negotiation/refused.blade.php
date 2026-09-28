<x-mail::message>
# Prix refusé

**{{ $buyer->name }}** a refusé votre proposition pour la commande **{{ $order->order_number }}**
({{ $vendor->store_name }}).

@if($reason)
## Raison indiquée

> {{ $reason }}
@endif

La discussion reste ouverte — vous pouvez proposer un nouveau prix.

<x-mail::button :url="\App\Filament\Vendor\Resources\OrderResource::getUrl('view', ['record' => $order])">
Voir la commande et répondre
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
