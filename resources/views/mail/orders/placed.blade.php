<x-mail::message>
# Commande effectuée avec succès

Merci d'avoir passé commande chez nous. Nous vous tiendrons informé de l'état de votre commande. Votre numéro de commande est : {{ $order->order_number }}.

<x-mail::button :url="$url">
Voir Commande
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
