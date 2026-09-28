<x-mail::message>
# Négociation de prix

**{{ $buyer->name }}** souhaite discuter le prix de sa commande **{{ $order->order_number }}**
sur votre boutique **{{ $vendor->store_name }}**.

## Détails

**Total actuel :** {{ number_format((float) $order->grand_total, 2) }} {{ $vendor->currency->code ?? '' }}
@if($rfq?->target_price)
**Prix souhaité par le client :** {{ number_format((float) $rfq->target_price, 2) }} {{ $vendor->currency->code ?? '' }}
@endif

## Message du client

> {{ $rfq?->messages?->first()?->message ?? '—' }}

<x-mail::button :url="\App\Filament\Vendor\Resources\OrderResource::getUrl('view', ['record' => $order])">
Voir la commande et répondre
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
