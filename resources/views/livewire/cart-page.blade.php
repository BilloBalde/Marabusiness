<div class="bg-gray-50 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4">
        @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.cart'])
        <h1 class="text-2xl font-bold mb-4">Votre Panier</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Left Column: Cart Items -->
            <div class="md:col-span-3 space-y-6">
                @forelse ($grouped_cart as $vendor_id => $vendorGroup)
                    @php
                        $vendor = App\Models\Vendor::find($vendor_id);
                        $allVendorItemsSelected = isset($selected_vendor_totals[$vendor_id]);
                    @endphp

                    <div class="bg-white rounded-xl shadow p-4">
                        <!-- Vendor Header -->
                        <div class="flex justify-between items-center mb-3">
                            <h2 class="font-semibold text-lg">🏬 {{ $vendor->store_name ?? 'Vendeur inconnu' }}</h2>
                            
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input 
                                    type="checkbox"
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
                            @endphp
                            
                            <div class="bg-gray-50 rounded-xl p-4 flex gap-4 items-center relative mb-3">
                                <!-- Selection Checkbox -->
                                <input 
                                    type="checkbox"
                                    class="absolute top-3 left-3"
                                    wire:click="toggleItem('{{ $item['cart_key'] }}')"
                                    {{ $isSelected ? 'checked' : '' }}
                                >
                                
                                <!-- Product Image -->
                                <img src="{{ url('uploads/' . ($item['image'] ?? 'no.jpg')) }}"
                                     class="w-24 h-24 rounded-lg object-cover border">
                                
                                <!-- Product Details -->
                                <div class="flex-1 ml-4">
                                    <h3 class="font-semibold text-sm line-clamp-2">
                                        {{ $item['product_name'] ?? 'Produit sans nom' }}
                                    </h3>
                                    
                                    <!-- Display Variation Notes -->
                                    @if(!empty($variationText))
                                        <div class="mt-1 text-xs text-gray-600 bg-blue-50 p-2 rounded">
                                            <span class="font-medium">📝 Notes:</span>
                                            {{ $variationText }}
                                        </div>
                                    @endif
                                    
                                    <!-- Wholesale Badge -->
                                    @if($item['wholesale_applied'] ?? false)
                                        <span class="inline-block mt-1 px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                                            📦 Prix en gros
                                        </span>
                                    @endif
                                    
                                    <!-- Unit Price -->
                                    <p class="text-[#D4AF37] font-bold text-lg mt-1">
                                        {{ Number::currency($item['unit_amount'] ?? 0, $item['currency'] ?? 'USD') }}
                                    </p>
                                    
                                    <!-- Quantity Controls -->
                                    <div class="flex items-center gap-3 mt-3">
                                        <button 
                                            wire:click="decreaseQty('{{ $item['cart_key'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="decreaseQty"
                                            class="w-8 h-8 flex items-center justify-center rounded-full border bg-gray-100 hover:bg-gray-200 disabled:opacity-50"
                                        >
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                        
                                        <div class="relative">
                                            <input 
                                                type="number" 
                                                min="1"
                                                wire:model.live.debounce.500ms="quantity_values.{{ $item['cart_key'] }}"
                                                wire:change="updateQty('{{ $item['cart_key'] }}', $event.target.value)"
                                                class="w-16 text-center border rounded py-1"
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
                                            class="w-8 h-8 flex items-center justify-center rounded-full border bg-gray-100 hover:bg-gray-200 disabled:opacity-50"
                                        >
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Total Price -->
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Total</p>
                                    <p class="font-semibold text-lg">
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
                                    class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition">
                                    <i class="fa-solid fa-trash text-lg"></i>
                                </button>
                            </div>
                        @endforeach
                        
                        <!-- Vendor Subtotal -->
                        <div class="flex justify-between items-center mt-4 pt-4 border-t">
                            <span class="text-gray-600">Sous-total {{ $vendor->store_name ?? 'Vendeur' }}:</span>
                            <div class="text-right">
                                <p class="font-semibold">
                                    {{ Number::currency($vendorGroup['subtotal'] ?? 0, $vendorGroup['items'][0]['currency'] ?? 'USD') }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    ≈ {{ Number::currency($vendorGroup['subtotal_usd'] ?? 0, 'USD') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <!-- Empty Cart Message -->
                    <div class="text-center py-10 bg-white rounded-xl shadow">
                        <p class="text-gray-500 text-lg mb-4">Votre panier est vide</p>
                        <a href="/products"
                           class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-[#D4AF37] rounded-lg hover:bg-[#C9A227]">
                            Continuer Shopping
                            <i class="fa-solid fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                @endforelse
                @if(!empty($grouped_cart))
                    <div class="text-center py-10 bg-white rounded-xl shadow">
                        <a href="/products"
                           class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-[#D4AF37] rounded-lg hover:bg-[#C9A227]">
                            Continuer Shopping
                            <i class="fa-solid fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                @endif
            </div>
            
            <!-- Right Column: Order Summary -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-xl shadow p-5 sticky top-24">
                    <h2 class="text-lg font-semibold mb-3">Résumé</h2>
                    
                    <!-- Summary Details -->
                    <div class="space-y-2 mb-3">
                        <div class="flex justify-between text-sm">
                            <span>Articles sélectionnés:</span>
                            <span>{{ count($selected_items) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Vendeurs:</span>
                            <span>{{ count($selected_vendor_totals) }}</span>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <!-- Total Amount -->
                    <div class="flex justify-between font-semibold text-lg">
                        <span>Total USD</span>
                        <span>{{ Number::currency($selected_total, 'USD') }}</span>
                    </div>
                    
                    <!-- Checkout Button -->
                    @if (count($selected_items) > 0)
                        @if(auth()->check())
                        <a href="{{ $this->getCheckoutUrl() }}"
                           class="block w-full mt-5 py-3 text-center text-white text-lg font-semibold rounded-lg
                                  bg-[#D4AF37] hover:bg-[#C9A227] shadow">
                            Passer à la caisse
                        </a>
                        @else
                        <p class="text-sm text-red-600 text-center font-medium">
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
                            class="block w-full mt-5 py-3 text-center text-gray-400 text-lg font-semibold rounded-lg
                                   bg-gray-100 cursor-not-allowed">
                            Passer à la caisse
                        </button>
                        <p class="text-xs text-gray-500 mt-2 text-center">
                            Sélectionnez des articles pour passer commande
                        </p>
                    @endif
                    
                    <!-- Additional Info -->
                    <div class="mt-4 pt-4 border-t">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Les prix en gros sont automatiquement appliqués selon la quantité totale par produit
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>