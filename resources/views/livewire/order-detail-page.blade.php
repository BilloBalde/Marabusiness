@php
    // Main currency for the order
    $currency = $order->currency 
        ?? optional($order->vendor?->currency)->code 
        ?? 'USD';

    $carrierLabels = [
        'local' => 'Livraison locale (48h)',
        'chrono' => 'Chronopost',
        'dhl' => 'DHL Express',
        'ups' => 'UPS',
        'fedex' => 'FedEx',
        'other' => 'Autre transporteur',
    ];

    $trackingUrls = [
        'dhl' => 'https://www.dhl.com/global-en/home/tracking.html?tracking-id=',
        'ups' => 'https://www.ups.com/track?loc=en_US&tracknum=',
        'fedex' => 'https://www.fedex.com/fedextrack/?tracknumbers=',
        'chrono' => 'https://www.chronopost.fr/tracking-no-cms/suivi-page?listeNumerosLT=',
        'local' => null,
        'other' => null,
    ];

    $shipment = $order->latestShipment;
@endphp

<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.my-order', 'hasSub' => true, 'subContent' => 'ui.navbar.orders', 'subLink' => 'orders'])

    <!-- HEADER -->
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-700">Détails de la commande</h1>

        <a href="{{ route('my-orders') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-[#D4AF37] 
                  rounded-lg hover:bg-[#C9A227] shadow">
            ← Retour à mes commandes
        </a>
    </div>

    <!-- TOP CARDS -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

        <!-- CLIENT -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex gap-4">
            <div class="flex justify-center items-center w-12 h-12 bg-gray-100 rounded-lg">
                <i class="fa-solid fa-user text-gray-500"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Client</p>
                <p class="mt-1 font-semibold text-gray-700">{{ ($address == 'no address') ? 'no address' : $address->full_name }}</p>
            </div>
        </div>

        <!-- DATE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex gap-4">
            <div class="flex justify-center items-center w-12 h-12 bg-gray-100 rounded-lg">
                <i class="fa-solid fa-calendar text-gray-500"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Date</p>
                <p class="mt-1 font-semibold text-gray-700">{{ $order->created_at->format('Y-m-d') }}</p>
            </div>
        </div>

        <!-- ORDER STATUS - Add the cancel button -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex gap-4">
            <div class="flex justify-center items-center w-12 h-12 bg-gray-100 rounded-lg">
                <i class="fa-solid fa-box text-gray-500"></i>
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-500 uppercase">Statut Commande</p>

                @php
                    $statusColor = match($order->status) {
                        'new'       => 'bg-[#3B82F6]',
                        'processing' => 'bg-[#F59E0B]',
                        'shipped'   => 'bg-[#8B5CF6]',
                        'delivered' => 'bg-[#16A34A]',
                        'cancelled' => 'bg-[#DC2626]',
                        default     => 'bg-gray-500'
                    };
                @endphp

                <div class="flex items-center justify-between mt-1 flex-wrap gap-2">
                    <span class="inline-block px-3 py-1 text-white rounded-lg text-sm shadow {{ $statusColor }}">
                        {{ ucfirst($order->status) }}
                    </span>
                    
                    {{-- Cancel Button - Only show for orders with status 'new' --}}
                    @if($order->status === 'new')
                        <button 
                            wire:click="openCancelModal"
                            class="inline-flex items-center px-3 py-1 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition"
                        >
                            <i class="fas fa-times-circle mr-1"></i>
                            Annuler la commande
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Add the cancellation modal at the bottom of the file -->
        @if($showCancelModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <!-- Background overlay -->
                    <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" wire:click="closeCancelModal"></div>

                    <!-- Modal panel -->
                    <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <!-- Icon -->
                                <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="text-red-600 fas fa-exclamation-triangle"></i>
                                </div>
                                
                                <!-- Content -->
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                        Annuler la commande #{{ $order->order_number }}
                                    </h3>
                                    
                                    <div class="mt-4">
                                        <p class="text-sm text-gray-500">
                                            Êtes-vous sûr de vouloir annuler cette commande ? Cette action est irréversible.
                                        </p>
                                        
                                        <div class="mt-4">
                                            <label for="cancellationReason" class="block text-sm font-medium text-gray-700">
                                                Raison de l'annulation <span class="text-red-500">*</span>
                                            </label>
                                            <textarea
                                                wire:model="cancellationReason"
                                                id="cancellationReason"
                                                rows="3"
                                                class="form-controlblock w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                                                placeholder="Veuillez indiquer pourquoi vous annulez cette commande..."
                                            ></textarea>
                                            @error('cancellationReason')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        
                                        <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <i class="text-yellow-600 fas fa-info-circle"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm font-medium text-yellow-800">
                                                        Informations importantes
                                                    </h3>
                                                    <div class="mt-2 text-sm text-yellow-700">
                                                        <ul class="pl-5 list-disc">
                                                            <li>Les articles seront remis en stock</li>
                                                            <li>Si un paiement a été effectué, il sera remboursé</li>
                                                            <li>Le remboursement peut prendre 3-5 jours ouvrés</li>
                                                            <li>Cette action ne peut pas être annulée</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal actions -->
                        <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="cancelOrder"
                                wire:loading.attr="disabled"
                                class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                <span wire:loading.remove wire:target="cancelOrder">
                                    Confirmer l'annulation
                                </span>
                                <span wire:loading wire:target="cancelOrder">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Traitement...
                                </span>
                            </button>
                            <button
                                wire:click="closeCancelModal"
                                class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:w-auto sm:text-sm"
                            >
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- PAYMENT STATUS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex gap-4">
            <div class="flex justify-center items-center w-12 h-12 bg-gray-100 rounded-lg">
                <i class="fa-solid fa-credit-card text-gray-500"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Statut Paiement</p>

                @php
                    $payColor = match($order->payment_status) {
                        'paid'     => 'bg-[#16A34A]',
                        'pending'  => 'bg-[#F59E0B]',
                        'unpaid'   => 'bg-[#DC2626]',
                        'refunded' => 'bg-[#3B82F6]',
                        default    => 'bg-gray-500',
                    };
                @endphp

                <span class="mt-1 inline-block px-3 py-1 text-white rounded-lg text-sm shadow {{ $payColor }}">
                    {{ ucfirst($order->payment_status) }}
                </span>
            </div>
        </div>

    </div>

    <!-- CONTENT -->
    <div class="mt-8 flex flex-col gap-6 md:flex-row">

        <!-- LEFT: ITEMS + ADDRESS -->
        <div class="md:w-3/4 space-y-6">

            <!-- ORDER ITEMS -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Articles de la commande</h2>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[280px]">
                        <thead class="border-b">
                            <tr>
                                <th class="py-3 px-2 md:px-4 text-left text-sm font-semibold">Produit</th>
                                <th class="py-3 px-2 md:px-4 text-left text-sm font-semibold hidden sm:table-cell">Prix</th>
                                <th class="py-3 px-2 md:px-4 text-left text-sm font-semibold hidden sm:table-cell">Qté</th>
                                <th class="py-3 px-2 md:px-4 text-left text-sm font-semibold">Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                $total_amount = 0;
                            @endphp
                            @foreach ($order_items as $item)
                            <tr class="border-b">
                                <!-- Product Column (always visible) -->
                                <td class="py-4 px-2 md:px-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                        <div class="flex items-center gap-3">
                                            @php
                                            $image = $item->product->images[0] ?? 'default.png';
                                            @endphp

                                            <img class="w-12 h-12 sm:w-16 sm:h-16 object-cover rounded"
                                                src="{{ url('uploads/' . $image) }}"
                                                alt="{{ $item->product->name }}" />

                                            <div class="flex-1 min-w-0">
                                                <span class="font-semibold text-sm sm:text-base block truncate">
                                                    {{ $item->product->name }}
                                                </span>
                                                <!-- Mobile-only calculation breakdown -->
                                                <div class="sm:hidden mt-2">
                                                    <div class="text-gray-600 text-sm">
                                                        <span class="font-medium">{{ Number::currency($item->unit_amount, $currency) }}</span>
                                                        <span> × {{ $item->quantity }}</span>
                                                        <span class="mx-2">=</span>
                                                        <span class="font-semibold text-gray-800">
                                                            {{ Number::currency($item->total_amount, $currency) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Price Column (hidden on mobile) -->
                                <td class="py-4 px-2 md:px-4 text-gray-700 hidden sm:table-cell">
                                    <div class="text-sm md:text-base">
                                        {{ Number::currency($item->unit_amount, $currency) }}
                                    </div>
                                </td>

                                <!-- Quantity Column (hidden on mobile) -->
                                <td class="py-4 px-2 md:px-4 text-gray-700 hidden sm:table-cell">
                                    <div class="text-sm md:text-base">
                                        {{ $item->quantity }}
                                    </div>
                                </td>

                                <!-- Total Column (visible on all screens) -->
                                <td class="py-4 px-2 md:px-4">
                                    <div class="font-semibold text-gray-800 text-sm md:text-base hidden sm:block">
                                        {{ Number::currency($item->total_amount, $currency) }}
                                    </div>
                                    @php
                                        $total_amount += $item->total_amount;
                                    @endphp
                                </td>
                            </tr>
                            @endforeach
                            
                            <!-- Grand Total Row -->
                            <tr class="bg-gray-50 border-t-2 border-gray-300">
                                <td colspan="3" class="py-4 px-2 md:px-4 text-right font-semibold hidden sm:table-cell">
                                    Total Général:
                                </td>
                                <td class="py-4 px-2 md:px-4 font-bold text-gray-900 text-lg">
                                    <div class="flex flex-col sm:block">
                                        <div class="sm:hidden mb-1 text-sm font-semibold text-gray-700">
                                            Total Général:
                                        </div>
                                        <div class="text-right sm:text-left">
                                            {{ Number::currency($total_amount, $currency) }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DELIVERY ADDRESS -->
            @if ($address != 'no address')
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Adresse Livraison</h2>

                <div class="flex justify-between">
                    <div class="text-gray-700">
                        <p>{{ $address->street_address }}</p>
                        <p>{{ $address->city }}, {{ $address->state }}, {{ $address->zip_code }}</p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-700">Téléphone:</p>
                        <p class="text-gray-600">{{ $address->phone }}</p>
                    </div>
                </div>
            </div>   
            @endif

            @if ($shipment)
            <div class="bg-white p-6 rounded-xl shadow-sm border border-blue-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-blue-800">Suivi d'expédition</h2>
                    <button
                        wire:click="syncTracking"
                        wire:loading.attr="disabled"
                        wire:target="syncTracking"
                        class="inline-flex items-center px-3 py-2 text-xs font-semibold tracking-wide text-white rounded-full bg-emerald-600 hover:bg-emerald-700 shadow-sm">
                        <span wire:loading.remove wire:target="syncTracking">
                            <i class="fas fa-sync-alt mr-2"></i> Sync Tracking
                        </span>
                        <span wire:loading wire:target="syncTracking">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Sync...
                        </span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-blue-700 font-medium">Transporteur</p>
                        <p class="font-semibold">
                            {{ $carrierLabels[$shipment->carrier] ?? $shipment->carrier ?? 'Non spécifié' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-blue-700 font-medium">Numéro de suivi</p>
                        <p class="font-semibold font-mono">{{ $shipment->tracking_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-blue-700 font-medium">Statut</p>
                        <p class="font-semibold">
                            @if($shipment->status === 'delivered') ✅ Livré
                            @elseif($shipment->status === 'shipped') 🚚 Expédié
                            @elseif($shipment->status === 'in_transit') 🚚 En transit
                            @elseif($shipment->status === 'pending') ⏳ En attente
                            @else {{ ucfirst(str_replace('_',' ', $shipment->status)) }}
                            @endif
                        </p>
                    </div>
                    @if($shipment->current_location)
                        <div class="md:col-span-3">
                            <p class="text-sm text-blue-700 font-medium">Dernière position</p>
                            <p class="font-semibold">{{ $shipment->current_location }}</p>
                        </div>
                    @endif
                    @if($shipment->estimated_delivery_at)
                        <div class="md:col-span-3">
                            <p class="text-sm text-blue-700 font-medium">Livraison estimée</p>
                            <p class="font-semibold">{{ $shipment->estimated_delivery_at->format('Y-m-d') }}</p>
                        </div>
                    @endif
                </div>

                @php
                    $link = $trackingUrls[$shipment->carrier] ?? null;
                @endphp

                @if ($link)
                    <div class="mt-4">
                        <a href="{{ $link . $shipment->tracking_number }}" target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            Voir le suivi en ligne
                        </a>
                    </div>
                @endif
            </div>
            @endif

        </div>

        <!-- RIGHT: SUMMARY -->
        <div class="md:w-1/4">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">

                <h2 class="text-lg font-semibold text-gray-700 mb-4">Résumé</h2>

                <div class="flex justify-between mb-2 text-gray-700">
                    <span>Sous-total</span>
                    <span>{{ Number::currency($total_amount, $currency) }}</span>
                </div>

                <div class="flex justify-between mb-2 text-gray-700">
                    <span>Livraison</span>
                    <span>{{ Number::currency($order->shipping_amount ?? 0, $currency) }}</span>
                </div>

                <hr class="my-3">

                <div class="flex justify-between font-semibold text-gray-900 text-lg">
                    <span>Total</span>
                    <span>{{ Number::currency($order->grand_total, $currency) }}</span>
                </div>

            </div>
        </div>

    </div>

</div>
