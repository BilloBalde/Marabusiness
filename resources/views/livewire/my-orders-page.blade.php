<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
  <h1 class="text-4xl font-bold text-slate-500">Mes Commandes</h1>
  <div class="flex flex-col p-5 mt-4 bg-white rounded shadow-lg">
    <div class="-m-1.5 overflow-x-auto">
      <div class="p-1.5 min-w-full inline-block align-middle">
        <div class="overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
              <tr>
                <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-start">Numero Commande</th>
                <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-start">Date</th>
                <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-start">Statut Commande</th>
                <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-start">Statut Paiement</th>
                <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-start">Montant</th>
                <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-start">Total Paid</th>
                <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-start">Reste</th>
                <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-start">Monnaie</th>
                <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-end">Payer</th>
                <th scope="col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase text-end">Action</th>
              </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                <tr class="odd:bg-white even:bg-gray-100 dark:odd:bg-slate-900 dark:even:bg-slate-800" wire:key="{{ $order->id }}">
                    <td class="px-6 py-4 text-sm font-medium text-gray-800 whitespace-nowrap dark:text-gray-200">{{ $order->order_number }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap dark:text-gray-200">{{ $order->created_at->format('y-m-d') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap dark:text-gray-200"><span class="px-3 py-1 text-white bg-orange-500 rounded shadow">{{ $order->status }}</span></td>
                    <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap dark:text-gray-200"><span class="px-3 py-1 text-white bg-green-500 rounded shadow">{{ $order->payment_status }}</span></td>
                    <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap dark:text-gray-200">{{ $order->grand_total }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap dark:text-gray-200">{{ $order->total_paid }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap dark:text-gray-200">{{ $order->total_remaining }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800 whitespace-nowrap dark:text-gray-200">{{ $order->currency }}</td>
                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-end">
                        <button
                            wire:click="$dispatch('open-paiement-modal', { orderId: {{ $order->id }} })"
                            class="px-4 py-2 text-white rounded-md bg-green-600 hover:bg-slate-500"
                        >
                            Payer
                        </button>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-end">
                    <a href="/my-orders/{{ $order->id }}" class="px-4 py-2 text-white rounded-md bg-slate-600 hover:bg-slate-500">Voir Details</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
          </table>
        </div>
        @livewire('paiement-modal')
      </div>
      <div class="flex justify-end">
        <div class="px-4 py-3 text-sm font-medium text-gray-700">
          {{ $orders->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
