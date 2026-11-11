<div class="bg-gray-50 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4">
        @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.cart', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Votre panier</h1>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Left Column: Cart Items -->
            <div class="md:col-span-3 space-y-6">
                @forelse ($grouped_cart as $vendor_id => $vendorGroup)
                    @php
                        // Each cart line already carries its own vendor_name
                        // (CartManagement::getCartItemsFromCookie()) — no need
                        // to re-query the vendor here for every group.
                        $storeName = $vendorGroup['items'][0]['vendor_name'] ?? null;
                        $allVendorItemsSelected = isset($selected_vendor_totals[$vendor_id]);
                    @endphp

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <!-- Vendor Header -->
                        <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-100">
                            <h2 class="font-semibold text-lg text-gray-800 flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-full bg-[#F5E6B3] text-[#96731A] flex items-center justify-center text-sm">
                                    <i class="fa-solid fa-store"></i>
                                </span>
                                {{ $storeName ?? 'Vendeur inconnu' }}
                            </h2>

                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer hover:text-[#96731A] transition-colors">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 accent-[#D4AF37] text-[#D4AF37] focus:ring-[#D4AF37]/40"
                                    wire:click="toggleVendor('{{ $vendor_id }}')"
                                    {{ $allVendorItemsSelected ? 'checked' : '' }}
                                >
                                <span>Sélectionner vendeur</span>
                            </label>
                        </div>

                        @foreach ($vendorGroup['items'] as $item)
                            @php
                                $isSelected = in_array($item['cart_key'], $selected_items);
                                $variationText = $this->getVariationText($item);
                                $hasVariation = !empty($item['variation_id']) || !empty($variationText);
                            @endphp

                            <div class="bg-gray-50/70 rounded-xl p-4 mb-3 relative border border-transparent hover:border-gray-200 transition-colors">
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <!-- Left Column: Selection & Image -->
                                    <div class="flex items-start gap-3">
                                        <!-- Selection Checkbox -->
                                        <input
                                            type="checkbox"
                                            class="mt-2 rounded border-gray-300 accent-[#D4AF37] text-[#D4AF37] focus:ring-[#D4AF37]/40"
                                            wire:click="toggleItem('{{ $item['cart_key'] }}')"
                                            {{ $isSelected ? 'checked' : '' }}
                                        >

                                        <!-- Product Image -->
                                        <img src="{{ url('uploads/' . ($item['image'] ?? 'no.jpg')) }}"
                                             class="w-20 h-20 rounded-lg object-cover border border-gray-200 flex-shrink-0 bg-white">
                                    </div>

                                    <!-- Middle Column: Product Details -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-sm text-gray-800 line-clamp-2">
                                            {{ $item['product_name'] ?? 'Produit sans nom' }}
                                        </h3>

                                        <!-- Display Variation -->
                                        @if($hasVariation)
                                        <div class="mt-1.5 inline-flex items-center gap-1.5 text-xs text-gray-600 bg-white border border-gray-200 px-2 py-1 rounded-md">
                                            <i class="fa-solid fa-tag text-[#D4AF37]"></i>
                                            <span class="font-medium">Variation :</span>
                                            {{ $variationText }}
                                        </div>
                                        @endif

                                        <!-- Wholesale Badge -->
                                        @if($item['wholesale_applied'] ?? false)
                                            <span class="inline-flex items-center gap-1 mt-1.5 px-2 py-1 text-xs font-medium bg-green-50 text-green-700 border border-green-200 rounded-md">
                                                <i class="fa-solid fa-boxes-stacked"></i> Prix en gros
                                            </span>
                                        @endif

                                        <!-- Unit Price -->
                                        <p class="text-[#96731A] font-bold text-lg mt-1.5">
                                            {{ Number::currency($item['unit_amount'] ?? 0, $item['currency'] ?? 'USD') }}
                                        </p>

                                        <!-- Quantity Controls -->
                                        <div class="inline-flex items-center mt-3 bg-white border border-gray-200 rounded-lg overflow-hidden">
                                            <button
                                                wire:click="decreaseQty('{{ $item['cart_key'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="decreaseQty"
                                                class="w-9 h-9 flex items-center justify-center text-gray-600 hover:bg-[#F5E6B3]/60 hover:text-[#96731A] disabled:opacity-50 transition-colors"
                                            >
                                                <i class="fa-solid fa-minus text-xs"></i>
                                            </button>

                                            <div class="relative border-x border-gray-200">
                                                <input
                                                    type="number"
                                                    min="1"
                                                    wire:model.live.debounce.500ms="quantity_values.{{ $item['cart_key'] }}"
                                                    wire:change="updateQty('{{ $item['cart_key'] }}', $event.target.value)"
                                                    class="w-14 h-9 text-center text-sm font-medium border-0 focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                                >
                                                <div wire:loading wire:target="updateQty" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75">
                                                    <svg class="animate-spin h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </div>
                                            </div>

                                            <button
                                                wire:click="increaseQty('{{ $item['cart_key'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="increaseQty"
                                                class="w-9 h-9 flex items-center justify-center text-gray-600 hover:bg-[#F5E6B3]/60 hover:text-[#96731A] disabled:opacity-50 transition-colors"
                                            >
                                                <i class="fa-solid fa-plus text-xs"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Right Column: Total & Delete - Now in same row -->
                                    <div class="flex items-center justify-between sm:justify-end gap-4 mt-3 sm:mt-0">
                                        <!-- Total Price -->
                                        <div class="text-right">
                                            <p class="text-xs uppercase tracking-wide text-gray-400">Total</p>
                                            <p class="font-bold text-lg text-gray-800">
                                                {{ Number::currency($item['total_amount'] ?? 0, $item['currency'] ?? 'USD') }}
                                            </p>
                                            @if($item['wholesale_applied'] ?? false)
                                                <p class="text-xs text-green-600">
                                                    {{ $item['quantity'] ?? 1 }} × {{ Number::currency($item['unit_amount'] ?? 0, $item['currency'] ?? 'USD') }}
                                                </p>
                                            @endif
                                        </div>

                                        <!-- Remove Button -->
                                        <button
                                            wire:click="removeItem('{{ $item['cart_key'] }}')"
                                            wire:loading.attr="disabled"
                                            type="button"
                                            title="Retirer du panier"
                                            class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors self-start sm:self-center">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- Vendor Subtotal -->
                        <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                            <span class="text-gray-500 text-sm">Sous-total {{ $storeName ?? 'Vendeur' }} :</span>
                            <div class="text-right">
                                <p class="font-bold text-gray-800">
                                    {{ Number::currency($vendorGroup['subtotal'] ?? 0, $vendorGroup['items'][0]['currency'] ?? 'USD') }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    ≈ {{ Number::currency($vendorGroup['subtotal_usd'] ?? 0, 'USD') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <!-- Empty Cart Message -->
                    <div class="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="inline-flex w-16 h-16 items-center justify-center rounded-full bg-gray-100 mb-4">
                            <i class="fa-solid fa-cart-shopping text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-600 text-lg font-medium mb-5">Votre panier est vide</p>
                        <a href="/products"
                           class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-[#D4AF37] rounded-lg shadow-sm hover:bg-[#C9A227] hover:shadow-md transition-all">
                            Continuer mes achats
                            <i class="fa-solid fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                @endforelse
                @if(!empty($grouped_cart))
                    {{-- Was a second full white card holding one gold button, the same
                         weight as the actual cart above it. A quiet link is enough. --}}
                    <div class="text-center">
                        <a href="/products"
                           class="inline-flex items-center text-sm font-medium text-[#96731A] hover:text-[#D4AF37] transition-colors">
                            <i class="fa-solid fa-arrow-left mr-2 text-xs"></i>
                            Continuer mes achats
                        </a>
                    </div>
                @endif
            </div>

            <!-- Right Column: Order Summary -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sticky top-24">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 pb-4 border-b border-gray-100">Résumé</h2>

                    <!-- Summary Details -->
                    <div class="space-y-2.5 mb-4 text-sm text-gray-600">
                        <div class="flex justify-between">
                            <span>Articles sélectionnés :</span>
                            <span class="font-medium text-gray-800">{{ count($selected_items) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Vendeurs :</span>
                            <span class="font-medium text-gray-800">{{ count($selected_vendor_totals) }}</span>
                        </div>
                    </div>

                    <!-- Total Amount -->
                    <div class="flex justify-between items-baseline pt-4 border-t border-gray-100">
                        <span class="font-semibold text-gray-800">Total USD</span>
                        <span class="text-xl font-bold text-[#96731A]">{{ Number::currency($selected_total, 'USD') }}</span>
                    </div>

                    @if(!empty($grouped_cart))
                        <div class="mt-4 space-y-2">
                            <button
                                wire:click="confirmDeleteSelected"
                                @if (count($selected_items) === 0) disabled @endif
                                class="w-full py-2 text-sm font-medium rounded-lg border border-red-200 text-red-600 hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Supprimer la sélection
                            </button>
                            <button
                                wire:click="clearCartItems"
                                class="w-full py-2 text-sm font-medium rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                                Vider le panier
                            </button>
                        </div>
                    @endif

                    <!-- Checkout Button -->
                    @if (count($selected_items) > 0)
                        @if(auth()->check())
                        <a href="{{ $this->getCheckoutUrl() }}"
                           class="block w-full mt-5 py-3 text-center text-white text-base font-semibold rounded-lg
                                  bg-[#D4AF37] hover:bg-[#C9A227] shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                            Passer à la caisse
                        </a>
                        @else
                        <p class="mt-5 text-sm text-red-600 text-center font-medium">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            Connectez-vous pour passer commande
                        </p>
                        @endif
                        <p class="text-xs text-gray-500 mt-2 text-center">
                            {{ count($selected_items) }} article(s) sélectionné(s)
                        </p>
                    @else
                        <button
                            disabled
                            class="block w-full mt-5 py-3 text-center text-gray-400 text-base font-semibold rounded-lg
                                   bg-gray-100 cursor-not-allowed">
                            Passer à la caisse
                        </button>
                        <p class="text-xs text-gray-500 mt-2 text-center">
                            Sélectionnez des articles pour passer commande
                        </p>
                    @endif

                    <!-- Additional Info -->
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-400 leading-relaxed">
                            <i class="fas fa-info-circle mr-1"></i>
                            Les prix en gros sont automatiquement appliqués selon la quantité totale par produit
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($showDeleteSelectedModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black bg-opacity-50" wire:click="cancelDeleteSelected"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-trash"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">Supprimer la sélection ?</h3>
                        <p class="text-sm text-gray-600">
                            Tous les articles sélectionnés seront retirés de votre panier.
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button
                        wire:click="cancelDeleteSelected"
                        class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors">
                        Annuler
                    </button>
                    <button
                        wire:click="removeSelectedItems"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">
                        Oui, supprimer
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
