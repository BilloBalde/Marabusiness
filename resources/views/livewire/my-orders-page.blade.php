<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.orders'])
    <h1 class="text-3xl md:text-4xl font-bold text-slate-800 mb-4">
        Mes Commandes
    </h1>

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
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    N° Commande
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Date
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Statut Commande
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Statut Paiement
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Montant
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Total Payé
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Reste
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-start">
                                    Livraison
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-end">
                                    Payer
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-end">
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

                                <tr
                                    class="odd:bg-white even:bg-gray-50 hover:bg-gray-100 transition-colors"
                                    wire:key="order-{{ $order->id }}"
                                >
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
                                                'completed' => 'bg-emerald-600',
                                                'cancelled' => 'bg-red-500',
                                                default => 'bg-gray-500',
                                            };
                                        @endphp
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold text-white rounded-full shadow {{ $statusColor }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>

                                    {{-- Statut Paiement --}}
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        @php
                                            $payColor = match($order->payment_status) {
                                                'paid' => 'bg-emerald-600',
                                                'partial' => 'bg-amber-500',
                                                'pending' => 'bg-gray-500',
                                                default => 'bg-gray-500',
                                            };
                                        @endphp
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold text-white rounded-full shadow {{ $payColor }}">
                                            {{ ucfirst($order->payment_status) }}
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
                                        @if (($order->total_remaining ?? 0) > 0.00)
                                            <button
                                                wire:click="$dispatch('open-paiement-modal', { orderId: {{ $order->id }} })"
                                                class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-wide text-white rounded-full bg-emerald-600 hover:bg-emerald-700 shadow-sm"
                                            >
                                                Payer
                                            </button>
                                        @else
                                            <span class="text-xs text-emerald-600 font-semibold">
                                                Payé
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
                                        Vous n'avez pas encore de commande.
                                        <a href="/products" class="text-[#D4AF37] font-semibold hover:underline ml-1">
                                            Commencer vos achats
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Paiement modal --}}
                @livewire('paiement-modal')

                {{-- Pagination --}}
                <div class="flex justify-end mt-4 px-4 pb-4">
                    <div class="text-sm font-medium text-gray-700">
                        {{ $orders->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
