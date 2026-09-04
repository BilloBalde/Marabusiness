<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.orders', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    <h1 class="text-3xl md:text-4xl font-bold text-slate-800 mb-4">
        Mes Commandes
    </h1>

    {{-- Search Form --}}
    <form wire:submit.prevent="applySearch" class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <input
                type="text"
                wire:model.defer="search"
                placeholder="Rechercher par numéro de commande"
                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 pr-10 text-sm text-gray-800 shadow-sm focus:border-[#D4AF37] focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/30">
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-400">
                <i class="fas fa-search"></i>
            </span>
        </div>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-xl bg-[#D4AF37] px-5 py-3 text-sm font-semibold text-white shadow hover:bg-[#C9A227] transition">
            Rechercher
        </button>
    </form>

    {{-- Status Filter Tabs --}}
    <div class="mt-6">
        <div class="border-b border-gray-200">
            <nav class="flex flex-wrap gap-2 -mb-px" aria-label="Order status tabs">
                <button
                    wire:click="setStatus('all')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-t-lg transition-all duration-200
                        {{ $status === 'all' 
                            ? 'text-[#D4AF37] border-b-2 border-[#D4AF37] bg-[#D4AF37]/5' 
                            : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-list-ul text-xs"></i>
                    Tous
                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $status === 'all' ? 'bg-[#D4AF37] text-white' : 'bg-gray-100 text-gray-600' }}">
                        {{ $statusCounts['all'] }}
                    </span>
                </button>
                
                <button
                    wire:click="setStatus('new')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-t-lg transition-all duration-200
                        {{ $status === 'new' 
                            ? 'text-[#D4AF37] border-b-2 border-[#D4AF37] bg-[#D4AF37]/5' 
                            : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-clock text-xs"></i>
                    Nouveau
                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $status === 'new' ? 'bg-[#D4AF37] text-white' : 'bg-gray-100 text-gray-600' }}">
                        {{ $statusCounts['new'] }}
                    </span>
                </button>
                
                <button
                    wire:click="setStatus('processing')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-t-lg transition-all duration-200
                        {{ $status === 'processing' 
                            ? 'text-[#D4AF37] border-b-2 border-[#D4AF37] bg-[#D4AF37]/5' 
                            : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-cog text-xs"></i>
                    En traitement
                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $status === 'processing' ? 'bg-[#D4AF37] text-white' : 'bg-gray-100 text-gray-600' }}">
                        {{ $statusCounts['processing'] }}
                    </span>
                </button>
                
                <button
                    wire:click="setStatus('shipped')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-t-lg transition-all duration-200
                        {{ $status === 'shipped' 
                            ? 'text-[#D4AF37] border-b-2 border-[#D4AF37] bg-[#D4AF37]/5' 
                            : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-truck text-xs"></i>
                    Expédié
                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $status === 'shipped' ? 'bg-[#D4AF37] text-white' : 'bg-gray-100 text-gray-600' }}">
                        {{ $statusCounts['shipped'] }}
                    </span>
                </button>
                
                <button
                    wire:click="setStatus('delivered')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-t-lg transition-all duration-200
                        {{ $status === 'delivered' 
                            ? 'text-[#D4AF37] border-b-2 border-[#D4AF37] bg-[#D4AF37]/5' 
                            : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-check-circle text-xs"></i>
                    Livré
                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $status === 'delivered' ? 'bg-[#D4AF37] text-white' : 'bg-gray-100 text-gray-600' }}">
                        {{ $statusCounts['delivered'] }}
                    </span>
                </button>
                
                <button
                    wire:click="setStatus('cancelled')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-t-lg transition-all duration-200
                        {{ $status === 'cancelled' 
                            ? 'text-red-600 border-b-2 border-red-600 bg-red-50' 
                            : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <i class="fas fa-ban text-xs"></i>
                    Annulé
                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full {{ $status === 'cancelled' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600' }}">
                        {{ $statusCounts['cancelled'] }}
                    </span>
                </button>
            </nav>
        </div>
    </div>

    {{-- Active Filter Display (Optional) --}}
    @if($status !== 'all')
        <div class="mt-3 flex items-center gap-2 text-sm text-gray-600">
            <i class="fas fa-filter"></i>
            <span>Filtré par:</span>
            <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 rounded-full text-xs">
                @php
                    $statusLabels = [
                        'new' => 'Nouveau',
                        'processing' => 'En traitement',
                        'shipped' => 'Expédié',
                        'delivered' => 'Livré',
                        'cancelled' => 'Annulé',
                    ];
                @endphp
                {{ $statusLabels[$status] ?? ucfirst($status) }}
                <button wire:click="setStatus('all')" class="ml-1 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times-circle text-xs"></i>
                </button>
            </span>
        </div>
    @endif

    {{-- Orders Table --}}
    <div class="flex flex-col mt-4 bg-white rounded-2xl shadow-lg border border-gray-100">
        <div class="border-b border-gray-100 px-5 py-4 bg-gray-900 rounded-t-2xl">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#D4AF37]/20">
                    🧾
                </span>
                Historique de vos commandes
            </h2>
            <p class="text-xs text-[#D4AF37] mt-1">
                Suivez vos commandes, paiements et soldes restants.
            </p>
        </div>

        <div class="-m-1.5 overflow-x-auto">
            <div class="p-1.5 min-w-full inline-block align-middle">
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                             <tr>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    N° Commande
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Statut Commande
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Statut Paiement
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Montant
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Total Payé
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Reste
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Livraison
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-end">
                                    Payer
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-end">
                                    Action
                                </th>
                             </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($orders as $order)
                                @php
                                    $currencyCode = $order->currency
                                        ?? optional($order->vendor?->currency)->code
                                        ?? config('app.currency', 'USD');
                                @endphp

                                <tr class="odd:bg-white even:bg-gray-50 hover:bg-gray-100 transition-colors" wire:key="order-{{ $order->id }}">
                                    {{-- N° Commande --}}
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900 whitespace-nowrap">
                                        {{ $order->order_number }}
                                    </td>

                                    {{-- Date --}}
                                    <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                        {{ $order->created_at->format('Y-m-d') }}
                                    </td>

                                    {{-- Statut Commande --}}
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        @php
                                            $statusColor = match($order->status) {
                                                'new' => 'bg-amber-500',
                                                'processing' => 'bg-blue-500',
                                                'shipped' => 'bg-purple-500',
                                                'delivered' => 'bg-emerald-600',
                                                'cancelled' => 'bg-red-500',
                                                default => 'bg-gray-500',
                                            };
                                        @endphp
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold text-white rounded-full shadow {{ $statusColor }}">
                                            @if($order->status === 'new')
                                                Nouveau
                                            @elseif($order->status === 'processing')
                                                En traitement
                                            @elseif($order->status === 'shipped')
                                                Expédié
                                            @elseif($order->status === 'delivered')
                                                Livré
                                            @elseif($order->status === 'cancelled')
                                                Annulé
                                            @else
                                                {{ ucfirst($order->status) }}
                                            @endif
                                        </span>
                                    </td>

                                    {{-- Statut Paiement --}}
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        @php
                                            $payColor = match($order->payment_status) {
                                                'paid' => 'bg-emerald-600',
                                                'partial' => 'bg-amber-500',
                                                'pending' => 'bg-gray-500',
                                                'refunded' => 'bg-green-500',
                                                default => 'bg-gray-500',
                                            };
                                        @endphp
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold text-white rounded-full shadow {{ $payColor }}">
                                            @if($order->payment_status === 'paid')
                                                Payé
                                            @elseif($order->payment_status === 'partial')
                                                Partiel
                                            @elseif($order->payment_status === 'pending')
                                                En attente
                                            @elseif($order->payment_status === 'refunded')
                                                Remboursé
                                            @else
                                                {{ ucfirst($order->payment_status) }}
                                            @endif
                                        </span>
                                    </td>

                                    {{-- Montant (grand_total) --}}
                                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap font-semibold">
                                        {{ Number::currency($order->grand_total ?? 0, $currencyCode) }}
                                    </td>

                                    {{-- Total Payé --}}
                                    <td class="px-6 py-4 text-sm text-emerald-700 whitespace-nowrap">
                                        {{ Number::currency($order->total_paid ?? 0, $currencyCode) }}
                                    </td>

                                    {{-- Reste --}}
                                    <td class="px-6 py-4 text-sm whitespace-nowrap {{ ($order->total_remaining ?? 0) > 0 ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                        {{ Number::currency($order->total_remaining ?? 0, $currencyCode) }}
                                    </td>

                                    {{-- Livraison (shipping_amount) --}}
                                    <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap">
                                        {{ Number::currency($order->shipping_amount ?? 0, $currencyCode) }}
                                    </td>

                                    {{-- Payer button --}}
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-end">
                                        @if (($order->total_remaining ?? 0) > 0.00 && $order->status !== 'cancelled')
                                            <button
                                                wire:click="$dispatch('open-paiement-modal', { orderId: {{ $order->id }} })"
                                                class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-wide text-white rounded-full bg-emerald-600 hover:bg-emerald-700 shadow-sm"
                                            >
                                                Payer
                                            </button>
                                        @else
                                            <span class="text-xs text-emerald-600 font-semibold">
                                                {{ $order->status === 'cancelled' ? 'Annulé' : 'Payé' }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Voir Details --}}
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-end">
                                        <a href="/my-orders/{{ $order->id }}"
                                           class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-wide text-gray-900 rounded-full border border-gray-300 hover:bg-gray-900 hover:text-white transition">
                                            Détails
                                        </a>
                                    </td>
                                </tr>
                            @endforeach

                            @if ($orders->isEmpty())
                                <tr>
                                    <td colspan="11" class="px-6 py-10 text-center text-sm text-gray-500">
                                        @if($status !== 'all')
                                            Aucune commande avec le statut "{{ $statusLabels[$status] ?? $status }}".
                                            <button wire:click="setStatus('all')" class="text-[#D4AF37] font-semibold hover:underline ml-1">
                                                Voir toutes les commandes
                                            </button>
                                        @else
                                            Vous n'avez pas encore de commande.
                                            <a href="/products" class="text-[#D4AF37] font-semibold hover:underline ml-1">
                                                Commencer vos achats
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($orders->hasPages())
                    <div class="flex justify-end mt-4 px-4 pb-4">
                        <div class="text-sm font-medium text-gray-700">
                            {{ $orders->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>