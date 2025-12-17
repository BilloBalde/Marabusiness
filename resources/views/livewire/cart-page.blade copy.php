<div class="bg-gray-50 min-h-screen py-6">

    <div class="max-w-7xl mx-auto px-4">

        <h1 class="text-2xl font-bold mb-4">Votre Panier</h1>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

            <!-- =============================== -->
            <!-- 🛒 CART ITEMS (LEFT) -->
            <!-- =============================== -->
            <div class="md:col-span-3 space-y-4">

                @forelse ($cart_items as $item)
                <div class="bg-white rounded-xl shadow p-4 flex gap-4 items-center relative">

                    <!-- Remove -->
                    <button 
                        wire:click="removeItem({{ $item['product_id'] }})"
                        wire:loading.attr="disabled"
                        wire:target="removeItem"
                        class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition">
                        <i class="fa-solid fa-trash text-lg"></i>
                    </button>

                    <!-- Image -->
                    <img src="{{ url('uploads', $item['image']) }}"
                         class="w-24 h-24 rounded-lg object-cover border">

                    <!-- Info -->
                    <div class="flex-1">

                        <h3 class="font-semibold text-sm line-clamp-2">
                            {{ $item['name'] }} Vendu par {{ App\Models\Vendor::find($item['vendor_id'])->store_name }}
                        </h3>

                        <!-- Price -->
                        <p class="text-[#D4AF37] font-bold text-lg mt-1">
                            {{ Number::currency($item['unit_amount'] ?? 0, $item['currency'] ?? 'CAD') }}
                        </p>

                        <!-- Qty -->
                        <div class="flex items-center gap-3 mt-3">

                            <button 
                                wire:click="decreaseQty({{ $item['product_id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="decreaseQty"
                                class="w-8 h-8 flex items-center justify-center rounded-full border bg-gray-100 hover:bg-gray-200">
                                <i class="fa-solid fa-minus"></i>
                            </button>

                            <span class="font-semibold text-lg">{{ $item['quantity'] }}</span>

                            <button 
                                wire:click="increaseQty({{ $item['product_id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="increaseQty"
                                class="w-8 h-8 flex items-center justify-center rounded-full border bg-gray-100 hover:bg-gray-200">
                                <i class="fa-solid fa-plus"></i>
                            </button>

                        </div>

                    </div>

                    <!-- Total -->
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Total</p>
                        <p class="font-semibold text-lg">
                            {{ Number::currency($item['total_amount'] ?? 0, $item['currency'] ?? 'CAD') }}
                        </p>
                    </div>

                </div>
                @empty

                <!-- EMPTY STATE -->
                <div class="text-center py-10 bg-white rounded-xl shadow">
                    <p class="text-gray-500 text-lg mb-4">Votre panier est vide</p>

                    <a href="/products"
                       class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-[#D4AF37] rounded-lg hover:bg-[#C9A227]">
                        Continuer Shopping
                        <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                </div>

                @endforelse

            </div>

            <!-- =============================== -->
            <!-- 📦 SUMMARY (RIGHT) -->
            <!-- =============================== -->
            <div class="md:col-span-1">

                <div class="bg-white rounded-xl shadow p-5 sticky top-24">

                    <h2 class="text-lg font-semibold mb-3">Résumé</h2>

                    <div class="flex justify-between mb-1 text-sm">
                        <span>Sous-total</span>
                        <span>{{ Number::currency($grand_total ?? 0, 'CAD') }}</span>
                    </div>

                    <div class="flex justify-between mb-1 text-sm">
                        <span>Taxes</span>
                        <span>{{ Number::currency(0, 'CAD') }}</span>
                    </div>

                    <div class="flex justify-between mb-1 text-sm">
                        <span>Livraison</span>
                        <span class="text-green-600 font-semibold">Gratuit</span>
                    </div>

                    <hr class="my-3">

                    <div class="flex justify-between font-semibold text-lg">
                        <span>Total</span>
                        <span>{{ Number::currency($grand_total ?? 0, 'CAD') }}</span>
                    </div>

                    @if ($cart_items)
                        <a href="/checkout"
                           class="block w-full mt-5 py-3 text-center text-white text-lg font-semibold rounded-lg
                                  bg-[#D4AF37] hover:bg-[#C9A227] shadow">
                            Passer à la caisse
                        </a>
                    @endif

                </div>

            </div>

        </div>

    </div>

</div>
