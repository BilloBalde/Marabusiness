<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
  <h1 class="mb-4 text-2xl font-semibold">Votre Panier</h1>

  <div class="flex flex-col gap-6 md:flex-row">
    <!-- Cart Items Section -->
    <div class="md:w-3/4">
      <div class="p-6 overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="w-full min-w-[600px]">
          <thead>
            <tr class="text-left border-b">
              <th class="py-2 font-semibold">Produit</th>
              <th class="py-2 font-semibold">Prix</th>
              <th class="py-2 font-semibold">Quantité</th>
              <th class="py-2 font-semibold">Total</th>
              <th class="py-2 font-semibold">Supprimer</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($cart_items as $item)
              <tr wire:key='{{ $item['product_id'] }}' class="border-b">
                <td class="py-4">
                  <div class="flex items-center gap-4">
                    <img src="{{ url('uploads', $item['image']) }}" alt="Product" class="object-cover w-16 h-16">
                    <span class="font-medium">{{ $item['name'] }}</span>
                  </div>
                </td>
                <td class="py-4">{{ Number::currency($item['unit_amount'], 'CAD') }}</td>
                <td class="py-4">
                  <div class="flex items-center">
                    <button wire:click='decreaseQty({{ $item['product_id'] }})' class="px-3 py-1 border rounded-md">-</button>
                    <span class="mx-3">{{ $item['quantity'] }}</span>
                    <button wire:click='increaseQty({{ $item['product_id'] }})' class="px-3 py-1 border rounded-md">+</button>
                  </div>
                </td>
                <td class="py-4">{{ Number::currency($item['total_amount'], 'CAD') }}</td>
                <td class="py-4">
                  <button wire:click='removeItem({{ $item['product_id'] }})' class="px-3 py-1 text-sm bg-gray-200 border-2 rounded-lg hover:bg-red-500 hover:text-white hover:border-red-600">
                    <span wire:loading.remove wire:target='removeItem({{ $item['product_id'] }})'>Supprimer</span>
                    <span wire:loading wire:target='removeItem({{ $item['product_id'] }})'>Suppression...</span>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="py-6 text-lg text-center text-gray-500">Votre panier est vide,<a href="/products" class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                    Continuer Shopping
                    <svg class="w-4 h-4 ms-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                    </svg>
                </a></td>

              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Summary Section -->
    <div class="md:w-1/4">
      <div class="p-6 bg-white rounded-lg shadow-md">
        <h2 class="mb-4 text-lg font-semibold">Sommaire</h2>

        <div class="flex justify-between mb-2">
          <span>Sous-total</span>
          <span>{{ Number::currency($grand_total, 'CAD') }}</span>
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

        <div class="flex justify-between mb-2 font-semibold">
          <span>Total</span>
          <span>{{ Number::currency($grand_total, 'CAD') }}</span>
        </div>

        @if ($cart_items)
          <a href="/checkout" class="block w-full px-4 py-2 mt-4 text-center text-white bg-blue-600 rounded hover:bg-blue-700">
            Passer à la caisse
          </a>
        @endif
      </div>
    </div>
  </div>
</div>
