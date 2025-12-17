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
    @if(session()->has('email-errors'))
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>Note:</strong> Your order was placed successfully, but there were issues sending confirmation emails.
                        You can view your order details in your account dashboard.
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
    @endif
    <div class="max-w-5xl mx-auto">

        <h1 class="text-3xl font-bold text-gray-800 mb-4">
            🎉 Merci, votre commande est confirmée !
        </h1>

        <p class="text-gray-600 mb-8">
            Nous avons créé des commandes séparées pour chaque vendeur.
        </p>

        @foreach ($orders as $order)
            @php
                $shipment = $order->latestShipment;
                $vendor = $order->vendor;
            @endphp

            <div class="bg-white shadow rounded-lg mb-6 p-6 border border-gray-200">

                {{-- Vendor Header --}}
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold text-gray-800">
                        🏪 {{ $vendor?->store_name ?? 'Vendeur inconnu' }}
                    </h2>
                    <span class="text-[#D4AF37] font-bold text-xl">
                        {{ Number::currency($order->grand_total, $vendor->currency->code ?? 'USD') }}
                    </span>
                </div>

                {{-- Order Info --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

                    <div>
                        <p class="text-sm text-gray-600">Numéro de commande</p>
                        <p class="font-semibold">{{ $order->order_number }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Date</p>
                        <p class="font-semibold">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Méthode de Paiement</p>
                        <p class="font-semibold">
                            @if ($order->payment_method == 'cod')
                                Cash à la livraison
                            @elseif ($order->payment_method == 'om')
                                Orange Money
                            @else
                                Carte Bancaire
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Transporteur</p>
                        <p class="font-semibold">
                            {{ $order->shipping_carrier ?? 'Non spécifié' }} : {{ $order->shipping_amount }} {{ $vendor->currency->code ?? 'USD' }}
                        </p>
                    </div>

                </div>

                {{-- ITEMS --}}
                <div class="border-t pt-4 mt-4">
                    <h3 class="text-lg font-semibold mb-4">Articles</h3>

                    @foreach ($order->items as $item)
                        <div class="flex items-center gap-4 mb-4">

                            <img src="{{ url('uploads', $item->product->images[0] ?? '') }}"
                                 class="w-20 h-20 rounded-lg border object-cover">

                            <div class="flex-1">
                                <p class="font-semibold">{{ $item->product->name }}</p>
                                <p class="text-sm text-gray-500">Quantité : {{ $item->quantity }}</p>
                            </div>

                            <div class="font-semibold text-[#D4AF37]">
                                {{ Number::currency($item->total_amount, $vendor->currency->code ?? 'USD') }}
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Shipment --}}
                @if ($shipment)
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <h4 class="font-semibold text-blue-700 text-lg mb-2">📦 Suivi d’expédition</h4>

                        <p class="text-sm"><strong>Transporteur :</strong> {{ $shipment->carrier ?? $shipment->carrier }}</p>
                        <p class="text-sm"><strong>Numéro :</strong> {{ $shipment->tracking_number }}</p>
                        <p class="text-sm"><strong>Statut :</strong> {{ ucfirst(str_replace('_',' ', $shipment->status)) }}</p>

                        @php
                            $link = $trackingUrls[$shipment->carrier] ?? null;
                        @endphp

                        @if ($link)
                            <a href="{{ $link . $shipment->tracking_number }}" target="_blank"
                               class="block mt-3 py-2 px-4 bg-blue-600 text-white rounded-lg text-center">
                                Voir le suivi en ligne
                            </a>
                        @endif
                    </div>
                @endif

            </div>
        @endforeach

        {{-- Back to shop --}}
        <a href="/products"
           class="block w-full md:w-auto px-6 py-3 bg-[#D4AF37] text-white font-semibold rounded-lg shadow hover:bg-[#c9a227]">
            Retour à la Boutique
        </a>
    </div>
</section>
