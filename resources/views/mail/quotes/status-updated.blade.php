<x-mail::message>
# Votre devis a été mis à jour

Bonjour **{{ $client->name }}**,

Votre demande de devis pour **{{ $sourceProduct->name_text }}** a changé de statut :

@switch($quote->status)
    @case('processing')
Elle est maintenant **en cours de traitement** — nous préparons votre réponse.
        @break
    @case('validated')
Elle a été **validée**. Consultez le fil de discussion pour la suite.
        @break
    @case('cancelled')
Elle a été **annulée**. Vous pouvez nous écrire depuis le fil si besoin.
        @break
@endswitch

<x-mail::button :url="route('quotes.show', $quote->id)">
Voir mon devis
</x-mail::button>

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
