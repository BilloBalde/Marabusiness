<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            {{-- Was "My Requests for Quotation (RFQs)" in English, describing the
                 wholesale quote flow this table was originally built for. The same
                 rows now also carry price negotiations opened from checkout, which
                 is what a buyer actually reaches this page for. --}}
            <h1 class="text-3xl font-bold text-gray-900">Mes négociations</h1>
            <p class="text-gray-600 mt-2">Suivez vos discussions de prix avec les boutiques</p>
        </div>

        {{-- Cette page ne montre que les négociations où l'on est CLIENT. Un
             vendeur qui arrive ici par le menu voyait une page vide, alors que des
             clients attendaient son prix dans son espace boutique : exact, et
             parfaitement trompeur. --}}
        @if(!is_null($shopNegotiations))
            <div class="mb-6 rounded-xl border border-[#D4AF37] bg-amber-50 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="font-medium text-gray-900">Vous êtes aussi vendeur</p>
                    <p class="text-sm text-gray-700 mt-1">
                        @if($shopNegotiations > 0)
                            {{ $shopNegotiations }} client(s) discutent le prix avec votre boutique.
                            Vous répondez et fixez le prix depuis votre espace vendeur, pas depuis cette page.
                        @else
                            Cette page liste vos achats. Les demandes adressées à votre boutique
                            se traitent depuis votre espace vendeur.
                        @endif
                    </p>
                </div>
                <a href="{{ url('/vendor/orders?activeTab=negotiating') }}"
                   class="whitespace-nowrap px-4 py-2 bg-[#D4AF37] text-white rounded-lg hover:bg-[#c9a12f] font-medium text-sm text-center">
                    <i class="fas fa-store mr-2"></i>
                    Ouvrir mon espace vendeur
                    @if($shopNegotiations > 0)
                        <span class="ml-1 bg-white/25 rounded-full px-2">{{ $shopNegotiations }}</span>
                    @endif
                </a>
            </div>
        @endif

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-blue-100 text-blue-600">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">En attente du vendeur</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $rfqs->where('status', 'pending')->count() }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-green-100 text-green-600">
                        <i class="fas fa-file-invoice-dollar text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Prix proposé</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $rfqs->where('status', 'quoted')->count() }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-purple-100 text-purple-600">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Prix accepté</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $rfqs->where('status', 'accepted')->count() }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-gray-100 text-gray-600">
                        <i class="fas fa-ban text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $rfqs->total() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-xl shadow mb-6 p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Search -->
                <div class="flex-1">
                    <div class="relative">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search"
                            placeholder="Rechercher par boutique, produit ou n° de commande..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                
                <!-- Status Filter -->
                <div class="flex items-center space-x-4">
                    <select 
                        wire:model.live="status"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">Tous les statuts</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    
                    {{-- Was "New RFQ" pointing at /products, from the wholesale flow.
                         A price negotiation starts from the basket at checkout, not
                         from a product page, so that is where this sends you. --}}
                    <a href="{{ route('cart') }}" class="px-4 py-2 bg-[#D4AF37] text-white rounded-lg hover:bg-[#c9a12f] font-medium whitespace-nowrap">
                        <i class="fas fa-shopping-cart mr-2"></i> Mon panier
                    </a>
                </div>
            </div>
        </div>

        <!-- RFQs Table -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            @if($rfqs->isEmpty())
                <!-- Empty State -->
                <div class="text-center py-12">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-file-invoice-dollar text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune négociation</h3>
                    <p class="text-gray-500 mb-6">
                        Depuis votre panier, au moment de commander, vous pouvez proposer
                        un prix à une boutique avant de payer.
                    </p>
                    <a href="{{ route('cart') }}" class="px-6 py-3 bg-[#D4AF37] text-white rounded-lg hover:bg-[#c9a12f] font-medium">
                        <i class="fas fa-shopping-cart mr-2"></i> Voir mon panier
                    </a>
                </div>
            @else
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                {{-- "Objet" plutôt que "Produit" : une ligne porte soit un
                                     produit (demande de devis), soit une commande entière
                                     (négociation ouverte depuis le panier). --}}
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Objet
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Boutique
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantité
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Prix souhaité
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Envoyée le
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($rfqs as $rfq)
                                <tr class="hover:bg-gray-50">
                                    <!-- Objet : une commande, ou un produit -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($rfq->order)
                                                <div class="w-10 h-10 bg-[#D4AF37]/10 rounded-lg flex items-center justify-center mr-3">
                                                    <i class="fas fa-shopping-bag text-[#D4AF37]"></i>
                                                </div>
                                            @elseif($rfq->product && $rfq->product->images && count($rfq->product->images) > 0)
                                                <img
                                                    src="{{ url('uploads/' . $rfq->product->images[0]) }}"
                                                    alt="{{ $rfq->product->name }}"
                                                    class="w-10 h-10 rounded-lg object-cover mr-3"
                                                >
                                            @else
                                                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                                                    <i class="fas fa-box text-gray-400"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    @if($rfq->order)
                                                        Commande {{ $rfq->order->order_number }}
                                                    @else
                                                        {{ $rfq->product->name ?? 'Produit retiré' }}
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    @if($rfq->order)
                                                        {{ $rfq->order->items->count() }} article(s) du panier
                                                    @else
                                                        Demande n°{{ $rfq->id }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Boutique -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $rfq->vendor->store_name ?? 'Boutique retirée' }}</div>
                                        <div class="text-xs text-gray-500">{{ $rfq->vendor->city ?? '' }}</div>
                                    </td>

                                    <!-- Quantité -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ number_format($rfq->quantity, 0, ',', ' ') }} article(s)
                                        </div>
                                    </td>

                                    <!-- Prix souhaité, et le prix proposé par le vendeur s'il y en a un -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($rfq->target_price)
                                            <div class="text-sm text-gray-900">
                                                {{ number_format($rfq->target_price, 2, ',', ' ') }} {{ $rfq->currency }}
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">Non précisé</span>
                                        @endif
                                        @if($rfq->order && $rfq->order->hasLiveOffer())
                                            <div class="text-xs text-green-700 font-medium mt-1">
                                                {{-- Même devise que le prix souhaité : l'une et l'autre
                                                     sont dans la devise de la boutique. --}}
                                                Proposé : {{ number_format((float) $rfq->order->negotiated_total, 2, ',', ' ') }} {{ $rfq->currency }}
                                            </div>
                                        @endif
                                    </td>
                                    
                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'quoted' => 'bg-green-100 text-green-800',
                                                'accepted' => 'bg-blue-100 text-blue-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                                'cancelled' => 'bg-gray-100 text-gray-800',
                                            ];
                                        @endphp
                                        {{-- $statuses vient du composant : les mêmes libellés que
                                             le filtre, au lieu du statut brut en anglais. --}}
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$rfq->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statuses[$rfq->status] ?? ucfirst($rfq->status) }}
                                        </span>
                                        @if($rfq->order && $rfq->order->offerHasExpired())
                                            <div class="text-xs text-orange-600 mt-1">Prix expiré</div>
                                        @endif
                                    </td>

                                    <!-- Envoyée le -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $rfq->created_at->format('d/m/Y') }}
                                        <div class="text-xs">{{ $rfq->created_at->format('H:i') }}</div>
                                    </td>
                                    
                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            {{-- Le modal de détails affiche des devis (BulkRfqOffer), que
                                                 la négociation de panier n'utilise pas : sa fiche est la
                                                 commande elle-même. --}}
                                            @if($rfq->order)
                                                <a
                                                    href="{{ route('my-orders.show', $rfq->order->id) }}"
                                                    class="text-blue-600 hover:text-blue-900"
                                                    title="Voir la commande"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @else
                                                <button
                                                    wire:click="$dispatch('open-rfq-modal', {rfqId: {{ $rfq->id }} })"
                                                    class="text-blue-600 hover:text-blue-900"
                                                    title="Voir le détail"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @endif

                                            <!-- Discussion -->
                                            <a
                                                href="{{ route('rfq.chat', $rfq) }}"
                                                class="text-green-600 hover:text-green-900"
                                                title="Discuter avec la boutique"
                                            >
                                                <i class="fas fa-comment"></i>
                                            </a>

                                            <!-- Annuler (uniquement tant que rien n'est conclu) -->
                                            @if(in_array($rfq->status, ['pending', 'quoted']))
                                                <button
                                                    wire:click="cancelRfq({{ $rfq->id }})"
                                                    wire:confirm="Annuler cette négociation ? La commande correspondante sera annulée."
                                                    class="text-red-600 hover:text-red-900"
                                                    title="Annuler la négociation"
                                                >
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden space-y-4 p-4">
                    @foreach($rfqs as $rfq)
                        <div class="bg-gray-50 rounded-lg p-4 border">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-medium text-gray-900">
                                        @if($rfq->order)
                                            Commande {{ $rfq->order->order_number }}
                                        @else
                                            {{ $rfq->product->name ?? 'Produit retiré' }}
                                        @endif
                                    </h3>
                                    <p class="text-xs text-gray-500">
                                        @if($rfq->order)
                                            {{ $rfq->order->items->count() }} article(s) du panier
                                        @else
                                            Demande n°{{ $rfq->id }}
                                        @endif
                                    </p>
                                </div>
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'quoted' => 'bg-green-100 text-green-800',
                                        'accepted' => 'bg-blue-100 text-blue-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$rfq->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $statuses[$rfq->status] ?? ucfirst($rfq->status) }}
                                </span>
                            </div>

                            <!-- Détails -->
                            <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                                <div>
                                    <p class="text-gray-500">Boutique</p>
                                    <p class="font-medium">{{ $rfq->vendor->store_name ?? 'Boutique retirée' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Quantité</p>
                                    <p class="font-medium">{{ number_format($rfq->quantity, 0, ',', ' ') }} article(s)</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Prix souhaité</p>
                                    <p class="font-medium">
                                        @if($rfq->target_price)
                                            {{ number_format($rfq->target_price, 2, ',', ' ') }} {{ $rfq->currency }}
                                        @else
                                            <span class="text-gray-400">Non précisé</span>
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Envoyée le</p>
                                    <p class="font-medium">{{ $rfq->created_at->format('d/m/Y') }}</p>
                                </div>
                                @if($rfq->order && $rfq->order->hasLiveOffer())
                                    <div class="col-span-2">
                                        <p class="text-gray-500">Prix proposé par la boutique</p>
                                        <p class="font-semibold text-green-700">
                                            {{ number_format((float) $rfq->order->negotiated_total, 2, ',', ' ') }} {{ $rfq->currency }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex justify-end space-x-3 pt-3 border-t">
                                @if($rfq->order)
                                    <a
                                        href="{{ route('my-orders.show', $rfq->order->id) }}"
                                        class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                                    >
                                        <i class="fas fa-eye mr-1"></i> Commande
                                    </a>
                                @else
                                    <button
                                        wire:click="$dispatch('open-rfq-modal', {rfqId: {{ $rfq->id }} })"
                                        class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                                    >
                                        <i class="fas fa-eye mr-1"></i> Détails
                                    </button>
                                @endif
                                <a
                                    href="{{ route('rfq.chat', $rfq) }}"
                                    class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700"
                                >
                                    <i class="fas fa-comment mr-1"></i> Discuter
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $rfqs->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- RFQ Details Modal -->
    <div x-data="{ 
        show: false, 
        rfq: null,
        loading: true,
        quotes: []
    }" 
    x-on:open-rfq-modal.window="
        show = true;
        loading = true;
        rfq = $event.detail.rfqId;
        fetch(`/api/rfq/${$event.detail.rfqId}`)
            .then(response => response.json())
            .then(data => {
                rfq = data.rfq;
                quotes = data.quotes || [];
                loading = false;
            });
    ">
        <!-- Modal -->
        <div x-show="show" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background -->
                <div x-on:click="show = false" class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

                <!-- Modal Panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <!-- Loading -->
                    <template x-if="loading">
                        <div class="p-12 text-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-blue-600 mb-4"></i>
                            <p class="text-gray-600">Chargement du détail...</p>
                        </div>
                    </template>

                    <!-- Content -->
                    <template x-if="!loading && rfq">
                        <div>
                            <!-- Header -->
                            <div class="bg-gray-50 px-6 py-4 border-b">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">
                                            Demande n°<span x-text="rfq.id"></span>
                                            <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full"
                                                  :class="{
                                                      'bg-yellow-100 text-yellow-800': rfq.status === 'pending',
                                                      'bg-green-100 text-green-800': rfq.status === 'quoted',
                                                      'bg-blue-100 text-blue-800': rfq.status === 'accepted',
                                                      'bg-red-100 text-red-800': rfq.status === 'rejected',
                                                      'bg-gray-100 text-gray-800': rfq.status === 'cancelled'
                                                  }">
                                                {{-- Mêmes libellés que le tableau et le filtre. --}}
                                                <span x-text="@js($statuses)[rfq.status] ?? rfq.status"></span>
                                            </span>
                                        </h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Envoyée le <span x-text="new Date(rfq.created_at).toLocaleDateString('fr-FR')"></span>
                                        </p>
                                    </div>
                                    <button x-on:click="show = false" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="px-6 py-6">
                                <!-- Product Info -->
                                <div class="mb-8">
                                    <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Le produit</h4>
                                    <div class="flex items-start space-x-4">
                                        <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                                            <template x-if="rfq.product && rfq.product.images && rfq.product.images.length > 0">
                                                <img :src="`/uploads/${rfq.product.images[0]}`" class="w-full h-full object-cover rounded-lg">
                                            </template>
                                            <template x-if="!rfq.product || !rfq.product.images || rfq.product.images.length === 0">
                                                <i class="fas fa-box text-2xl text-gray-400"></i>
                                            </template>
                                        </div>
                                        <div>
                                            <h5 class="font-medium text-gray-900" x-text="rfq.product?.name || 'Produit retiré'"></h5>
                                            <p class="text-sm text-gray-500" x-text="rfq.product?.description ? rfq.product.description.substring(0, 100) + '...' : 'Pas de description'"></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- RFQ Details -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">La demande</h4>
                                        <dl class="space-y-3">
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Quantité demandée :</dt>
                                                <dd class="text-sm font-medium text-gray-900">
                                                    <span x-text="new Intl.NumberFormat('fr-FR').format(rfq.quantity)"></span> article(s)
                                                </dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Prix souhaité :</dt>
                                                <dd class="text-sm font-medium text-gray-900">
                                                    {{-- Le montant était formaté avec
                                                         Intl.NumberFormat(…, {style:'currency', currency: rfq.currency}) :
                                                         une devise absente ou inconnue lève un RangeError qui
                                                         vide tout le modal. Le code de devise est simplement
                                                         accolé, comme partout ailleurs dans la page. --}}
                                                    <template x-if="rfq.target_price">
                                                        <span x-text="`${new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2 }).format(rfq.target_price)} ${rfq.currency ?? ''}`"></span>
                                                    </template>
                                                    <template x-if="!rfq.target_price">
                                                        <span class="text-gray-400">Non précisé</span>
                                                    </template>
                                                </dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Personnalisation :</dt>
                                                <dd class="text-sm font-medium text-gray-900">
                                                    <span x-text="rfq.needs_customization ? 'Oui' : 'Non'"></span>
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">La livraison</h4>
                                        <dl class="space-y-3">
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Pays :</dt>
                                                <dd class="text-sm font-medium text-gray-900" x-text="rfq.shipping_country || 'Non précisé'"></dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Ville :</dt>
                                                <dd class="text-sm font-medium text-gray-900" x-text="rfq.shipping_city || 'Non précisée'"></dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-500">Port :</dt>
                                                <dd class="text-sm font-medium text-gray-900" x-text="rfq.shipping_port || 'Non précisé'"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>

                                <!-- Customization Notes -->
                                <template x-if="rfq.customization_notes">
                                    <div class="mb-8">
                                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Notes de personnalisation</h4>
                                        <div class="bg-gray-50 p-4 rounded-lg">
                                            <p class="text-gray-700" x-text="rfq.customization_notes"></p>
                                        </div>
                                    </div>
                                </template>

                                <!-- Quotes Section -->
                                <template x-if="quotes.length > 0">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Propositions de la boutique</h4>
                                        <div class="space-y-4">
                                            <template x-for="quote in quotes" :key="quote.id">
                                                <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                                    <div class="flex justify-between items-start mb-3">
                                                        <div>
                                                            <h5 class="font-medium text-gray-900">Proposition de <span x-text="rfq.vendor?.store_name"></span></h5>
                                                            <p class="text-sm text-gray-500">
                                                                Reçue le <span x-text="new Date(quote.created_at).toLocaleDateString('fr-FR')"></span>
                                                            </p>
                                                        </div>
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                            Prix proposé
                                                        </span>
                                                    </div>

                                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                        <div>
                                                            <p class="text-gray-500">Prix unitaire</p>
                                                            <p class="font-medium text-lg text-green-600">
                                                                <span x-text="`${new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2 }).format(quote.unit_price)} ${quote.currency ?? ''}`"></span>
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <p class="text-gray-500">Quantité minimum</p>
                                                            <p class="font-medium">
                                                                <span x-text="new Intl.NumberFormat('fr-FR').format(quote.moq)"></span> article(s)
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <p class="text-gray-500">Délai</p>
                                                            <p class="font-medium" x-text="`${quote.lead_time_days} jour(s)`"></p>
                                                        </div>
                                                        <div>
                                                            <p class="text-gray-500">Conditions de livraison</p>
                                                            <p class="font-medium" x-text="quote.shipping_terms"></p>
                                                        </div>
                                                    </div>

                                                    <template x-if="quote.vendor_notes">
                                                        <div class="mt-4 pt-4 border-t">
                                                            <p class="text-gray-500 text-sm mb-2">Note de la boutique :</p>
                                                            <p class="text-gray-700" x-text="quote.vendor_notes"></p>
                                                        </div>
                                                    </template>

                                                    <div class="mt-4 pt-4 border-t flex justify-end space-x-3">
                                                        {{-- « Accept Quote » était un <button> sans le moindre
                                                             gestionnaire : cliquer dessus ne faisait rien. C'est
                                                             la page de discussion qui accepte une proposition
                                                             (RfqChat, via RfqOfferConverter), donc le bouton y
                                                             mène au lieu de faire semblant. --}}
                                                        <a :href="`/rfq/${rfq.id}/chat`"
                                                           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                                            <i class="fas fa-comment mr-2"></i> Discuter et accepter
                                                        </a>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="quotes.length === 0 && rfq.status === 'pending'">
                                    <div class="text-center py-8 bg-gray-50 rounded-lg">
                                        <i class="fas fa-clock text-3xl text-yellow-500 mb-3"></i>
                                        <h5 class="font-medium text-gray-900 mb-2">En attente de la boutique</h5>
                                        <p class="text-gray-500">La boutique a été prévenue et vous répondra dans la discussion.</p>
                                    </div>
                                </template>
                            </div>

                            <!-- Footer -->
                            <div class="bg-gray-50 px-6 py-4 border-t flex justify-between">
                                {{-- Le lien « Contact Support » pointait sur href="#" : il ne
                                     menait nulle part. La page de contact existe. --}}
                                <div class="text-sm text-gray-500">
                                    {{-- url() et non route() : /contact est déclaré sans nom
                                         (routes/web.php:63). --}}
                                    Besoin d'aide ? <a href="{{ url('/contact') }}" class="text-blue-600 hover:text-blue-800">Nous écrire</a>
                                </div>
                                <div class="space-x-3">
                                    <button x-on:click="show = false"
                                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                        Fermer
                                    </button>
                                    <a :href="`/rfq/${rfq.id}/chat`"
                                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                        <i class="fas fa-comment mr-2"></i> Discuter
                                    </a>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Un <script src="//unpkg.com/alpinejs"> chargeait ici une seconde copie
         d'Alpine par-dessus celle que Livewire 3 embarque déjà (@livewireScripts,
         components/layouts/app.blade.php:58). Alpine refuse de démarrer deux fois
         — « Detected multiple instances of Alpine running » — et tout ce qui suit
         sur la page, y compris les confirmations wire:confirm, s'arrête là. --}}
</div>