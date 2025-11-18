<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="flex items-center justify-between mb-6">
    <h1 class="text-4xl font-bold text-slate-500">Order Details</h1>
    @auth
    <a href="{{ route('my-orders') }}"
       class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700">
        ← Retour aux Commandes
    </a>
    @endauth
    </div>



  <!-- Grid -->
  <div class="grid gap-4 mt-5 sm:grid-cols-2 lg:grid-cols-4 sm:gap-6">
    <!-- Card -->
    <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800">
      <div class="flex p-4 md:p-5 gap-x-4">
        <div class="flex-shrink-0 flex justify-center items-center size-[46px] bg-gray-100 rounded-lg dark:bg-gray-800">
          <svg class="flex-shrink-0 text-gray-600 size-5 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
        </div>

        <div class="grow">
          <div class="flex items-center gap-x-2">
            <p class="text-xs tracking-wide text-gray-500 uppercase">
              Client
            </p>
          </div>
          <div class="flex items-center mt-1 gap-x-2">
            <div>{{ $address->full_name }}</div>
          </div>
        </div>
      </div>
    </div>
    <!-- End Card -->

    <!-- Card -->
    <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800">
      <div class="flex p-4 md:p-5 gap-x-4">
        <div class="flex-shrink-0 flex justify-center items-center size-[46px] bg-gray-100 rounded-lg dark:bg-gray-800">
          <svg class="flex-shrink-0 text-gray-600 size-5 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 22h14" />
            <path d="M5 2h14" />
            <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22" />
            <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2" />
          </svg>
        </div>

        <div class="grow">
          <div class="flex items-center gap-x-2">
            <p class="text-xs tracking-wide text-gray-500 uppercase">
              Date
            </p>
          </div>
          <div class="flex items-center mt-1 gap-x-2">
            <h3 class="text-xl font-medium text-gray-800 dark:text-gray-200">
              {{ $order->created_at->format('Y-m-d') }}
            </h3>
          </div>
        </div>
      </div>
    </div>
    <!-- End Card -->

    <!-- Card -->
    <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800">
      <div class="flex p-4 md:p-5 gap-x-4">
        <div class="flex-shrink-0 flex justify-center items-center size-[46px] bg-gray-100 rounded-lg dark:bg-gray-800">
          <svg class="flex-shrink-0 text-gray-600 size-5 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 11V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6" />
            <path d="m12 12 4 10 1.7-4.3L22 16Z" />
          </svg>
        </div>

        <div class="grow">
          <div class="flex items-center gap-x-2">
            <p class="text-xs tracking-wide text-gray-500 uppercase">
              Commande Status
            </p>
          </div>
          @php
            $statusColor = match($order->status) {
                'pending' => 'bg-yellow-500',
                'completed' => 'bg-green-500',
                'cancelled' => 'bg-red-500',
                'new' => 'bg-blue-500',
            };

            $paymentStatusColor = match($order->payment_status) {
                'paid' => 'bg-green-500',
                'unpaid' => 'bg-red-500',
                'refunded' => 'bg-blue-500',
                'pending' => 'bg-yellow-500',
            };
            @endphp
          <div class="flex items-center mt-1 gap-x-2">
            <span class="px-3 py-1 text-white rounded shadow {{ $statusColor }}">{{ $order->status }}</span>
          </div>
        </div>
      </div>
    </div>
    <!-- End Card -->

    <!-- Card -->
    <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800">
      <div class="flex p-4 md:p-5 gap-x-4">
        <div class="flex-shrink-0 flex justify-center items-center size-[46px] bg-gray-100 rounded-lg dark:bg-gray-800">
          <svg class="flex-shrink-0 text-gray-600 size-5 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12s2.545-5 7-5c4.454 0 7 5 7 5s-2.546 5-7 5c-4.455 0-7-5-7-5z" />
            <path d="M12 13a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" />
            <path d="M21 17v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2" />
            <path d="M21 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2" />
          </svg>
        </div>

        <div class="grow">
          <div class="flex items-center gap-x-2">
            <p class="text-xs tracking-wide text-gray-500 uppercase">
              Status Paiement
            </p>
          </div>
          <div class="flex items-center mt-1 gap-x-2">
            <span class="px-3 py-1 text-white rounded shadow {{ $paymentStatusColor }}">{{ $order->payment_status }}</span>
          </div>
        </div>
      </div>
    </div>
    <!-- End Card -->
  </div>
  <!-- End Grid -->

  <div class="flex flex-col gap-4 mt-4 md:flex-row">
    <div class="md:w-3/4">
      <div class="p-6 mb-4 overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="w-full">
          <thead>
            <tr>
              <th class="font-semibold text-left">Produit</th>
              <th class="font-semibold text-left">Prix</th>
              <th class="font-semibold text-left">Quantité</th>
              <th class="font-semibold text-left">Total</th>
            </tr>
          </thead>
          <tbody>

            <!--[if BLOCK]><![endif]-->
            @foreach ($order_items as $item)
            <tr wire:key="53">
              <td class="py-4">
                <div class="flex items-center">
                  <img class="w-16 h-16 mr-4" src="{{ url('uploads', $item->product->images[0]) }}"  alt="{{ $item->product->name }}" />
                  <span class="font-semibold">{{ $item->product->name }}</span>
                </div>
              </td>
              <td class="py-4">{{ Number::currency($item->unit_amount, 'CAD') }}</td>
              <td class="py-4">
                <span class="w-8 text-center">{{ $item->quantity }}</span>
              </td>
              <td class="py-4">{{ Number::currency($item->total_amount, 'CAD') }}</td>
            </tr>
            @if($order_items->isEmpty())
                <tr>
                    <td colspan="4" class="py-4 text-center text-gray-500">No items in this order.</td>
                </tr>
                @endif
            @endforeach

            <!--[if ENDBLOCK]><![endif]-->

          </tbody>
        </table>
      </div>

      <div class="p-6 mb-4 overflow-x-auto bg-white rounded-lg shadow-md">
        <h1 class="mb-3 font-bold font-3xl text-slate-500">Adresse Livraison</h1>
        <div class="flex items-center justify-between">
          <div>
            <p>{{ $address->street_address }}, {{ $address->city }}, {{ $address->state }}, {{ $address->zip_code }}</p>
          </div>
          <div>
            <p class="font-semibold">Phone:</p>
            <p>{{ $address->phone }}</p>
          </div>
        </div>
      </div>

    </div>
    <div class="md:w-1/4">
      <div class="p-6 bg-white rounded-lg shadow-md">
        <h2 class="mb-4 text-lg font-semibold">Sommaire</h2>
        <div class="flex justify-between mb-2">
          <span>Subtotal</span>
          <span>{{ Number::currency($order->grand_total, 'CAD') }}</span>
        </div>
        <div class="flex justify-between mb-2">
          <span>Taxes</span>
          <span>{{ Number::currency(0, 'CAD') }}</span>
        </div>
        <div class="flex justify-between mb-2">
          <span>Livraison</span>
          <span>{{ Number::currency(0, 'CAD') }}</span>
        </div>
        <hr class="my-2">
        <div class="flex justify-between mb-2">
          <span class="font-semibold">Grand Total</span>
          <span class="font-semibold">{{ Number::currency($order->grand_total, 'CAD') }}</span>
        </div>

      </div>
    </div>
  </div>
</div>
