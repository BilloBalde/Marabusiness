{{-- resources/views/livewire/rfq-chat.blade.php --}}
<div class="min-h-screen bg-gray-50" wire:init="loadMessages">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white border-b sticky top-0 z-10">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('user.rfqs') }}" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        {{-- L'en-tête nommait le produit, que la négociation d'un panier
                             n'a pas : elle porte sur une commande entière. Le titre dit
                             donc de quoi on discute dans les deux cas. --}}
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">
                                @if($rfq->order)
                                    Négociation — commande {{ $rfq->order->order_number }}
                                @else
                                    Demande n°{{ $rfq->id }}
                                @endif
                            </h1>
                            <p class="text-sm text-gray-500">
                                @if($rfq->product)
                                    {{ $rfq->product->name }} •
                                @elseif($rfq->order)
                                    {{ $rfq->order->items->count() }} article(s) •
                                @endif
                                {{ $otherParty['type'] === 'vendor' ? 'Boutique' : 'Client' }} : {{ $otherParty['name'] }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900">
                            @php
                                $statusColors = [
                                    'pending' => 'text-yellow-600 bg-yellow-100',
                                    'quoted' => 'text-green-600 bg-green-100',
                                    'accepted' => 'text-blue-600 bg-blue-100',
                                    'rejected' => 'text-red-600 bg-red-100',
                                ];
                                $statusLabels = [
                                    'pending' => 'En attente du vendeur',
                                    'quoted' => 'Prix proposé',
                                    'accepted' => 'Prix accepté',
                                    'rejected' => 'Refusée',
                                    'cancelled' => 'Annulée',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs {{ $statusColors[$rfq->status] ?? 'text-gray-600 bg-gray-100' }}">
                                {{ $statusLabels[$rfq->status] ?? ucfirst($rfq->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ number_format($rfq->quantity, 0, ',', ' ') }} article(s)
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Négociation sur une commande : l'acheteur voit ce qu'il paie aujourd'hui,
             ce que le vendeur propose, et jusqu'à quand. Sans ce panneau le prix ne
             vivrait que dans le texte d'un message, sans moyen d'y donner suite. --}}
        @if($negotiatedOrder)
            <div class="p-6 pb-0 space-y-4">
                @if(session('error'))
                    <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            Commande {{ $negotiatedOrder->order_number }}
                        </h2>
                        <span class="text-xs px-2 py-1 rounded-full
                            {{ $negotiatedOrder->hasLiveOffer() ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700' }}">
                            {{ $negotiatedOrder->hasLiveOffer() ? 'Prix proposé' : 'En discussion' }}
                        </span>
                    </div>

                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Prix initial</dt>
                            <dd class="{{ $negotiatedOrder->hasLiveOffer() ? 'line-through text-gray-400' : 'text-gray-800' }}">
                                {{ number_format((float) $negotiatedOrder->pre_negotiation_total, 2) }}
                                {{ $negotiatedOrder->vendor?->currency?->code }}
                            </dd>
                        </div>

                        @if($rfq->target_price)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Votre prix souhaité</dt>
                                <dd class="text-gray-800">
                                    {{ number_format((float) $rfq->target_price, 2) }}
                                    {{ $negotiatedOrder->vendor?->currency?->code }}
                                </dd>
                            </div>
                        @endif

                        @if($negotiatedOrder->hasLiveOffer())
                            <div class="flex justify-between font-semibold text-[#D4AF37] text-base pt-1">
                                <dt>Prix proposé</dt>
                                <dd>
                                    {{ number_format((float) $negotiatedOrder->negotiated_total, 2) }}
                                    {{ $negotiatedOrder->vendor?->currency?->code }}
                                </dd>
                            </div>
                            <p class="text-xs text-gray-500">
                                Valable jusqu'au
                                {{ $negotiatedOrder->negotiated_expires_at?->format('d/m/Y à H:i') }}
                            </p>
                        @elseif($negotiatedOrder->offerHasExpired())
                            <p class="text-xs text-red-600 pt-1">
                                Le prix proposé a expiré. Demandez-en un nouveau au vendeur.
                            </p>
                        @endif
                    </dl>

                    @if($isBuyer && $negotiatedOrder->isNegotiating())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if($negotiatedOrder->hasLiveOffer())
                                <button wire:click="acceptNegotiatedPrice"
                                        wire:confirm="Accepter ce prix et passer au paiement ?"
                                        class="flex-1 bg-[#D4AF37] text-white text-sm font-medium px-4 py-2 rounded-lg">
                                    Accepter et payer
                                </button>
                                <button wire:click="refuseNegotiatedPrice"
                                        class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700">
                                    Refuser
                                </button>
                            @endif

                            <button wire:click="cancelNegotiation"
                                    wire:confirm="Annuler définitivement cette commande ?"
                                    class="px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg">
                                Annuler la commande
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Devis reçus : jusqu'ici l'acheteur ne voyait le prix que dans le texte d'un
             message, sans aucun moyen d'y donner suite. --}}
        @if($offers->isNotEmpty())
            <div class="p-6 pb-0 space-y-4">
                @if(session('error'))
                    <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    Devis reçus ({{ $offers->count() }})
                </h2>

                @foreach($offers as $offer)
                    <div class="bg-white border rounded-lg p-4 {{ $offer->isAccepted() ? 'border-green-400' : 'border-gray-200' }}">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-2 text-sm">
                                <div>
                                    <div class="text-xs text-gray-500">Prix unitaire</div>
                                    <div class="font-semibold text-gray-900">
                                        {{ number_format((float) $offer->unit_price, 2) }} {{ $offer->currency }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Quantité min.</div>
                                    <div class="font-semibold text-gray-900">{{ number_format($offer->moq) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Livraison</div>
                                    <div class="font-semibold text-gray-900">
                                        {{ number_format((float) $offer->shipping_cost, 2) }} {{ $offer->currency }}
                                        <span class="text-gray-500 font-normal">({{ $offer->shipping_terms }})</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Délai</div>
                                    <div class="font-semibold text-gray-900">{{ $offer->lead_time_days }} jours</div>
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="text-xs text-gray-500">Total</div>
                                <div class="text-lg font-bold text-gray-900">
                                    {{ number_format($offer->total(), 2) }} {{ $offer->currency }}
                                </div>
                            </div>
                        </div>

                        @if($offer->vendor_notes)
                            <p class="mt-3 text-sm text-gray-600 whitespace-pre-wrap">{{ $offer->vendor_notes }}</p>
                        @endif

                        @if($isBuyer && $rejectingOfferId === $offer->id)
                            {{-- Motif facultatif : il part au vendeur avec le refus. --}}
                            <div class="mt-4 border-t pt-4">
                                <label for="motif-{{ $offer->id }}" class="block text-sm font-medium text-gray-700">
                                    Motif du refus <span class="font-normal text-gray-500">(facultatif)</span>
                                </label>
                                <textarea id="motif-{{ $offer->id }}"
                                          wire:model="rejectionReason"
                                          rows="2"
                                          maxlength="1000"
                                          placeholder="Ex. : prix trop élevé pour cette quantité, délai trop long…"
                                          {{-- « border-gray-300 » seul ne pose pas de bordure : Tailwind
                                               y règle la couleur, pas l'épaisseur. Il manquait « border »
                                               et la marge intérieure. --}}
                                          class="mt-1 w-full p-3 rounded-lg border border-gray-300 text-sm focus:border-red-500 focus:ring-red-500"></textarea>
                                <div class="mt-3 flex items-center justify-end gap-3">
                                    <button type="button" wire:click="cancelRejecting"
                                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">
                                        Annuler
                                    </button>
                                    <button type="button"
                                            wire:click="rejectOffer({{ $offer->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="rejectOffer({{ $offer->id }})"
                                            class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 disabled:opacity-50">
                                        <span wire:loading.remove wire:target="rejectOffer({{ $offer->id }})">Confirmer le refus</span>
                                        <span wire:loading wire:target="rejectOffer({{ $offer->id }})">Envoi…</span>
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 flex items-center justify-end gap-3">
                                @if($offer->isAccepted())
                                    <span class="text-sm font-medium text-green-700">Devis accepté</span>
                                @elseif($offer->status === \App\Models\BulkRfqOffer::STATUS_REJECTED)
                                    <span class="text-sm text-gray-500">Refusé</span>
                                @elseif($isBuyer)
                                    <button type="button"
                                            wire:click="startRejecting({{ $offer->id }})"
                                            class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50">
                                        Refuser
                                    </button>
                                    <button type="button"
                                            wire:click="acceptOffer({{ $offer->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="acceptOffer({{ $offer->id }})"
                                            wire:confirm="Accepter ce devis ? Une commande sera créée au prix négocié."
                                            class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 disabled:opacity-50">
                                        <span wire:loading.remove wire:target="acceptOffer({{ $offer->id }})">Accepter et commander</span>
                                        <span wire:loading wire:target="acceptOffer({{ $offer->id }})">Création de la commande…</span>
                                    </button>
                                @else
                                    <span class="text-sm text-gray-500">En attente de la décision de l'acheteur</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Chat Messages -->
        <div class="p-6 space-y-6" id="chat-messages">
            @foreach($messages as $message)
                <div class="flex {{ $message['is_from_user'] && !$user->vendor ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-xs lg:max-w-md">
                        <div class="text-xs text-gray-500 mb-1 {{ $message['is_from_user'] && !$user->vendor ? 'text-right' : 'text-left' }}">
                            {{ $message['sender_name'] }} • {{ $message['created_at'] }}
                        </div>
                        <div class="p-3 rounded-lg {{ $message['is_from_user'] && !$user->vendor ? 'bg-blue-100 text-blue-900' : 'bg-gray-100 text-gray-900' }}">
                            <p class="whitespace-pre-wrap">{{ $message['message'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
            
            @if(empty($messages))
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-comment text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Démarrez la discussion</h3>
                    <p class="text-gray-500">Écrivez votre premier message à {{ $otherParty['name'] }}</p>
                </div>
            @endif
        </div>
        
        <!-- Message Input -->
        <div class="bg-white border-t sticky bottom-0">
            <div class="p-6">
                <form wire:submit.prevent="sendMessage" class="flex space-x-4">
                    <textarea
                        wire:model="newMessage"
                        rows="1"
                        placeholder="Écrivez votre message…"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                        x-data="{
                            resize() {
                                this.style.height = 'auto';
                                this.style.height = (this.scrollHeight) + 'px';
                            }
                        }"
                        x-init="resize()"
                        @input="resize()"
                    ></textarea>
                    <button 
                        type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium flex items-center"
                        wire:loading.attr="disabled"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>
                        <span wire:loading.remove>Envoyer</span>
                        <span wire:loading>
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Envoi…
                        </span>
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-2">
                    Press Enter to send, Shift+Enter for new line
                </p>
            </div>
        </div>
    </div>
    
    <!-- Scroll to bottom script -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Scroll to bottom on load
            scrollToBottom();
            
            // Listen for scroll event
            Livewire.on('scroll-to-bottom', () => {
                setTimeout(scrollToBottom, 100);
            });
            
            function scrollToBottom() {
                const chatMessages = document.getElementById('chat-messages');
                if (chatMessages) {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            }
            
            // Auto-resize textarea
            const textarea = document.querySelector('textarea');
            if (textarea) {
                textarea.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.form.dispatchEvent(new Event('submit', { cancelable: true }));
                    }
                });
            }
        });
    </script>
</div>