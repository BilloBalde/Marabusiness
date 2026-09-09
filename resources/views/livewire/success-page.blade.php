@php
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
@endphp

<section class="bg-gray-50 py-10 px-4 min-h-screen">
    <!-- Email Errors -->
    @if(session()->has('email-errors'))
        <div class="max-w-5xl mx-auto mb-6">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Note:</strong> Votre commande a été passée avec succès, mais il y a eu des problèmes d'envoi des emails de confirmation.
                            Vous pouvez consulter les détails de votre commande dans votre tableau de bord.
                        </p>
                        @if(is_array(session('email-errors')))
                            <div class="mt-2 text-xs text-yellow-600">
                                @foreach(session('email-errors') as $error)
                                    <p>• {{ $error }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Stripe Payment Status -->
    @if($session_id)
        @if($stripe_payment_completed)
            <div class="max-w-5xl mx-auto mb-6">
                <div class="bg-green-50 border-l-4 border-green-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                <strong>✅ Paiement Stripe confirmé !</strong> Votre paiement a été traité avec succès.
                            </p>
                            <p class="text-xs text-green-600 mt-1">
                                ID de transaction: {{ substr($session_id, 0, 15) }}...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="max-w-5xl mx-auto mb-6">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-spinner fa-spin text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>⏳ Vérification du paiement...</strong> Nous vérifions votre paiement Stripe.
                            </p>
                            <p class="text-xs text-blue-600 mt-1">
                                Cette vérification peut prendre quelques instants.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-block p-3 bg-gradient-to-r from-[#D4AF37] to-[#c9a12f] rounded-full mb-4">
                <i class="fas fa-check-circle text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">
                🎉 Merci, votre commande est confirmée !
            </h1>
            <p class="text-gray-600">
                Nous avons créé des commandes séparées pour chaque vendeur.
            </p>
        </div>

        <!-- Orders List -->
        @forelse ($orders as $order)
            @php
                $shipment = $order->latestShipment;
                $vendor = $order->vendor;
            @endphp

            <div class="bg-white shadow-lg rounded-xl mb-8 p-4 sm:p-6 border border-gray-200 hover:shadow-xl transition-shadow duration-300">

                {{-- Vendor Header with Payment Status --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 pb-4 border-b">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">
                            🏪 {{ $vendor?->store_name ?? 'Vendeur inconnu' }}
                        </h2>
                        @php
                            // Money the buyer says they have sent, which the vendor has
                            // not confirmed receiving yet. Without showing this, a buyer
                            // who has just declared a payment sees "En attente de
                            // paiement" and thinks nothing was recorded.
                            $declare = $order->declaredAwaitingConfirmation();
                        @endphp
                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-3 py-1 rounded-full text-sm font-semibold
                                @if($order->payment_status === 'paid') bg-green-100 text-green-800
                                @elseif($order->payment_status === 'partial') bg-yellow-100 text-yellow-800
                                @elseif($declare > 0) bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800 @endif">
                                @if($order->payment_status === 'paid') ✅ Payé
                                @elseif($order->payment_status === 'partial') ⏳ Paiement partiel
                                @elseif($declare > 0) 🕓 Paiement déclaré — en attente de validation du vendeur
                                @else ⏳ En attente de paiement
                                @endif
                            </span>
                            @if($order->payment_method === 'stripe')
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                    💳 Carte Bancaire
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 md:mt-0 text-left md:text-right">
                        <div class="text-[#D4AF37] font-bold text-2xl">
                            {{ Number::currency($order->grand_total, $vendor->currency->code ?? 'USD') }}
                        </div>
                        @if($declare > 0)
                            <div class="text-sm text-blue-700 mt-1">
                                {{ Number::currency($declare, $vendor->currency->code ?? 'USD') }} déclarés,
                                en attente de confirmation
                            </div>
                        @elseif($order->payment_status === 'partial' || $order->payment_status === 'pending')
                            <div class="text-sm text-gray-600 mt-1">
                                Reste à payer: {{ Number::currency($order->total_remaining, $vendor->currency->code ?? 'USD') }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Order Info Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Numéro de commande</p>
                        <p class="font-semibold text-gray-800">{{ $order->order_number }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 font-medium">Date</p>
                        <p class="font-semibold text-gray-800">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 font-medium">Méthode de Paiement</p>
                        <p class="font-semibold text-gray-800">
                            @if ($order->payment_method == 'cod')
                                💵 Cash à la livraison
                            @elseif ($order->payment_method == 'om')
                                🟠 Orange Money
                            @elseif ($order->payment_method == 'stripe')
                                💳 Carte Bancaire (Stripe)
                            @elseif ($order->payment_method == 'cash')
                                💵 Espèces
                            @else
                                {{ ucfirst($order->payment_method) }}
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 font-medium">Transporteur</p>
                        <p class="font-semibold text-gray-800">
                            {{ $carrierLabels[$order->shipping_carrier] ?? $order->shipping_carrier ?? 'À déterminer' }}
                        </p>
                        @if($order->shipping_amount > 0)
                            <p class="text-sm text-gray-600">
                                Coût: {{ Number::currency($order->shipping_amount, $vendor->currency->code ?? 'USD') }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- ITEMS --}}
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">📦 Articles commandés</h3>

                    <div class="space-y-4">
                        @foreach ($order->items as $item)
                            <div class="flex flex-col sm:flex-row sm:items-start gap-4 p-4 bg-white border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors">
                                <img src="{{ url('uploads', $item->product->images[0] ?? '') }}" style="max-width: 100px;"
                                     class="w-full sm:w-20 h-48 sm:h-20 max-h-48 sm:max-h-20 rounded-lg border object-cover object-center flex-shrink-0 bg-gray-100">

                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-800">{{ $item->product->name }}</p>
                                    <div class="flex flex-wrap items-center gap-4 mt-1">
                                        <p class="text-sm text-gray-600">Quantité : {{ $item->quantity }}</p>
                                        <p class="text-sm text-[#D4AF37] font-semibold">
                                            {{ Number::currency($item->unit_amount, $vendor->currency->code ?? 'USD') }}/unité
                                        </p>
                                    </div>
                                    
                                    @if($item->hasVariations())
                                        <div class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-100">
                                            <p class="font-medium text-blue-700 text-sm mb-2">📝 Variations sélectionnées:</p>
                                            <div class="space-y-1">
                                                @if(!empty($item->variation_note))
                                                    <p class="text-blue-600 text-sm">{{ $item->variation_note }}</p>
                                                @elseif(!empty($item->variation_json))
                                                    @foreach($item->variation_json as $attribute => $value)
                                                        @if(!in_array($attribute, ['note', 'custom_note']))
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-xs text-gray-500">•</span>
                                                                <span class="text-sm text-gray-700">{{ ucfirst($attribute) }}:</span>
                                                                <span class="text-sm font-medium text-gray-800">{{ $value }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                @endif
                                                
                                                {{-- Show custom note if separate --}}
                                                @if(!empty($item->variation_json['note']))
                                                    <div class="mt-2 pt-2 border-t border-blue-200">
                                                        <p class="text-xs text-blue-600">
                                                            <i class="fas fa-sticky-note mr-1"></i> Note: {{ $item->variation_json['note'] }}
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="text-left sm:text-right">
                                    <p class="font-bold text-lg text-[#D4AF37]">
                                        {{ Number::currency($item->total_amount, $vendor->currency->code ?? 'USD') }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Shipment Tracking --}}
                @if ($shipment)
                    <div class="mt-6 p-5 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl border border-blue-200">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center mr-3">
                                <i class="fas fa-truck text-white"></i>
                            </div>
                            <h4 class="font-bold text-blue-800 text-lg">📦 Suivi d'expédition</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-blue-700 font-medium">Transporteur</p>
                                <p class="font-semibold">{{ $carrierLabels[$shipment->carrier] ?? $shipment->carrier ?? 'Non spécifié' }}</p>
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
                        </div>

                        @php
                            $link = $trackingUrls[$shipment->carrier] ?? null;
                        @endphp

                        @if ($link)
                            <a href="{{ $link . $shipment->tracking_number }}" target="_blank"
                               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                                <i class="fas fa-external-link-alt mr-2"></i>
                                Voir le suivi en ligne
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Order Actions --}}
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('my-orders.show', $order->id) }}" 
                           class="flex-1 text-center px-4 py-3 border-2 border-[#D4AF37] text-[#D4AF37] font-semibold rounded-lg hover:bg-[#D4AF37] hover:text-white transition">
                            <i class="fas fa-eye mr-2"></i> Voir les détails complets
                        </a>
                        
                        @if($declare > 0)
                            {{-- Declaring again while the first one is still being checked
                                 would only produce a second claim for the same money. --}}
                            <span class="inline-flex items-center px-4 py-2 text-xs font-medium text-blue-800 bg-blue-50 border border-blue-200 rounded-full">
                                🕓 Paiement déclaré — le vendeur doit le confirmer
                            </span>
                        @elseif($order->payment_status === 'pending' && $order->payment_method !== 'stripe')
                            <button
                                wire:click="$dispatch('open-paiement-modal', { orderId: {{ $order->id }} })"
                                class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-wide text-white rounded-full bg-emerald-600 hover:bg-emerald-700 shadow-sm"
                            >
                                Payer Maintenant
                            </button>
                            {{-- <a href="{{ route('my-orders.show', $order->id) }}" 
                               class="flex-1 text-center px-4 py-3 bg-[#D4AF37] text-white font-semibold rounded-lg hover:bg-[#c9a12f] transition">
                                <i class="fas fa-credit-card mr-2"></i> Payer maintenant
                            </a> --}}
                        @endif
                        
                        @if($order->payment_method === 'stripe' && $order->payment_status === 'pending')
                            <button class="flex-1 text-center px-4 py-3 bg-gray-100 text-gray-400 font-semibold rounded-lg cursor-not-allowed">
                                <i class="fas fa-hourglass-half mr-2"></i> Paiement en traitement
                            </button>
                        @endif
                    </div>
                </div>

            </div>
        @empty
            {{-- No Orders --}}
            <div class="text-center py-16 bg-white rounded-xl shadow">
                <div class="mb-6">
                    <i class="fas fa-shopping-cart text-gray-300 text-6xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Aucune commande récente</h3>
                <p class="text-gray-500 mb-6">Vous n'avez pas de commandes aujourd'hui.</p>
                <a href="/products" 
                   class="inline-flex items-center px-6 py-3 bg-[#D4AF37] text-white font-semibold rounded-lg hover:bg-[#c9a12f] transition">
                    <i class="fas fa-store mr-2"></i> Découvrir les produits
                </a>
            </div>
        @endforelse

        {{-- Summary & Actions --}}
        @if(!$orders->isEmpty())
            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-between items-center">
                <a href="/products"
                   class="order-2 sm:order-1 w-full sm:w-auto px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Retour à la Boutique
                </a>
                
                <div class="order-1 sm:order-2 text-center sm:text-right">
                    <p class="text-lg font-semibold text-gray-800">
                        Total des commandes aujourd'hui: 
                        <span class="text-[#D4AF37]">
                            {{ Number::currency($todayTotal, $todayTotalCurrency) }}
                        </span>
                    </p>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $orders->count() }} commande(s) | {{ $orders->sum(fn($o) => $o->items->sum('quantity')) }} article(s)
                    </p>
                </div>
            </div>
        @endif
    </div>
</section>
