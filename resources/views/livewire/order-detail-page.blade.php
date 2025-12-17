@php
    // Main currency for the order
    $currency = $order->currency 
        ?? optional($order->vendor?->currency)->code 
        ?? 'USD';
@endphp

<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">

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

        <!-- ORDER STATUS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex gap-4">
            <div class="flex justify-center items-center w-12 h-12 bg-gray-100 rounded-lg">
                <i class="fa-solid fa-box text-gray-500"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Statut Commande</p>

                @php
                    $statusColor = match($order->status) {
                        'new'       => 'bg-[#3B82F6]',
                        'pending'   => 'bg-[#F59E0B]',
                        'completed' => 'bg-[#16A34A]',
                        'cancelled' => 'bg-[#DC2626]',
                        default     => 'bg-gray-500'
                    };
                @endphp

                <span class="mt-1 inline-block px-3 py-1 text-white rounded-lg text-sm shadow {{ $statusColor }}">
                    {{ ucfirst($order->status) }}
                </span>
            </div>
        </div>

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

                <table class="w-full">
                    <thead class="border-b">
                        <tr>
                            <th class="py-2 text-left">Produit</th>
                            <th class="py-2 text-left">Prix</th>
                            <th class="py-2 text-left">Qté</th>
                            <th class="py-2 text-left">Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $total_amount = 0;
                        @endphp
                        @foreach ($order_items as $item)
                        <tr class="border-b">
                            <td class="py-4">
                                <div class="flex items-center gap-4">
                                  @php
                                  $image = $item->product->images[0] ?? 'default.png';
                                  @endphp

                                  <img class="w-16 h-16 mr-4"
                                      src="{{ url('uploads/' . $image) }}"
                                      alt="{{ $item->product->name }}" />

                                  <span class="font-semibold">{{ $item->product->name }}</span>
                                </div>
                            </td>

                            <td class="py-4 text-gray-700">
                                {{ Number::currency($item->unit_amount, $currency) }}
                            </td>

                            <td class="py-4 text-gray-700">{{ $item->quantity }}</td>

                            <td class="py-4 font-semibold text-gray-800">
                                {{ Number::currency($item->total_amount, $currency) }}
                                @php
                                    $total_amount += $item->total_amount;
                                @endphp
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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
