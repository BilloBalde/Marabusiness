<div class="bg-gray-50 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- LEFT COLUMN — SHIPPING + PAYMENT FORM --}}
        <div class="lg:col-span-8 space-y-6">
            {{-- FLASH MESSAGES --}}
            @if(session()->has('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif
            
            @if(session()->has('shipping-success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('shipping-success') }}
                </div>
            @endif
            
            @if(session()->has('shipping-error'))
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg">
                    {{ session('shipping-error') }}
                </div>
            @endif
            
            {{-- SHIPPING ADDRESS FORM --}}
            <div class="bg-white shadow-md rounded-xl p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Delivery Address</h2>
                @auth
                <div class="bg-white shadow-md rounded-xl p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-xl font-bold text-gray-800">Saved Addresses</h2>

                        <button type="button"
                                wire:click="clearSelectedAddress"
                                class="text-sm px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200">
                            Use new address
                        </button>
                    </div>

                    @if(!empty($savedAddresses))
                        <div class="relative">
                            <select
                                wire:model="selected_address_id"
                                wire:change="applyAddress($event.target.value)"
                                class="w-full appearance-none rounded-xl border border-[#E6D8A3] bg-gradient-to-r from-[#FFF7DF] to-white px-4 py-3 pr-10 text-gray-800 shadow-sm transition focus:border-[#D4AF37] focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/40">
                                <option value="">Choose a saved address</option>
                                @foreach($savedAddresses as $addr)
                                    <option value="{{ $addr['id'] }}">
                                        {{ $addr['first_name'] }} {{ $addr['last_name'] }} — {{ $addr['street_address'] }}, {{ $addr['city'] }}
                                        @if(!empty($addr['is_default'])) (Default) @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[#8a6a00]">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
                            <input type="checkbox" wire:model="save_address" class="rounded">
                            <span class="text-sm text-gray-700">Save new address to my profile</span>
                        </div>
                    @else
                        <p class="text-sm text-gray-600">No saved addresses yet. Fill the form below to add one.</p>
                        <div class="mt-3 flex items-center gap-2">
                            <input type="checkbox" wire:model="save_address" class="rounded" checked>
                            <span class="text-sm text-gray-700">Save this address to my profile</span>
                        </div>
                    @endif
                </div>
                @endauth
                <br>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-gray-600">First Name *</label>
                        <input wire:model.blur="first_name" type="text" 
                               class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        @error('first_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    
                    <div>
                        <label class="text-gray-600">Last Name *</label>
                        <input wire:model.blur="last_name" type="text" 
                               class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        @error('last_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="text-gray-600">Phone *</label>
                    <input wire:model.blur="phone" type="tel" 
                           class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                    @error('phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                
                <div class="mt-4">
                    <label class="text-gray-600">Street Address *</label>
                    <textarea wire:model.blur="street_address" rows="2" 
                              class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent"></textarea>
                    @error('street_address')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    <div class="md:col-span-2">
                        <label class="text-gray-600">City *</label>
                        <input wire:model.blur="city" type="text" 
                               class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        @error('city')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    
                    <div>
                        <label class="text-gray-600">State *</label>
                        <input wire:model.blur="state" type="text" 
                               class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        @error('state')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    
                    <div>
                        <label class="text-gray-600">Zip Code *</label>
                        <input wire:model.blur="zip_code" type="text" 
                               class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        @error('zip_code')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="text-gray-600">Country *</label>
                    <select wire:model.blur="country" 
                            class="mt-1 w-full p-3 border rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        <option value="Guinea">Guinea</option>
                        <option value="Senegal">Senegal</option>
                        <option value="Ivory Coast">Ivory Coast</option>
                        <option value="Mali">Mali</option>
                        <option value="France">France</option>
                        <option value="United States">United States</option>
                        <option value="Canada">Canada</option>
                        <option value="United Kingdom">United Kingdom</option>
                        <option value="Other">Other</option>
                    </select>
                    @error('country')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                
                {{-- GEOLOCATION --}}
                <div class="mt-6 border-t pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Location (Optional)</h3>
                            <p class="text-sm text-gray-500">For accurate shipping zone calculation</p>
                        </div>
                        <button type="button" wire:click="getLocation" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="getLocation">📍 Get My Location</span>
                            <span wire:loading wire:target="getLocation">Getting location...</span>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-gray-600">Latitude</label>
                            <input wire:model.blur="latitude" type="number" step="0.000001" 
                                   class="mt-1 w-full p-3 border rounded-lg" placeholder="e.g., 9.945587">
                        </div>
                        <div>
                            <label class="text-gray-600">Longitude</label>
                            <input wire:model.blur="longitude" type="number" step="0.000001" 
                                   class="mt-1 w-full p-3 border rounded-lg" placeholder="e.g., -9.696677">
                        </div>
                    </div>
                    
                    @if($has_location)
                        <p class="text-sm text-green-600 mt-2">
                            ✅ Location detected. Shipping will be calculated accurately.
                        </p>
                    @endif
                </div>
                
                {{-- CALCULATE SHIPPING BUTTON --}}
                <div class="mt-6">
                    <button type="button" wire:click="calculateShipping" 
                            wire:loading.attr="disabled"
                            wire:target="calculateShipping"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition disabled:opacity-50">
                        <span wire:loading.remove wire:target="calculateShipping">
                            🚚 Calculate Shipping Costs
                        </span>
                        <span wire:loading wire:target="calculateShipping">
                            🔄 Calculating shipping...
                        </span>
                    </button>
                </div>
            </div>
            
            {{-- SHIPPING OPTIONS --}}
            @if($has_shipping_calculated && !empty($availableCarriers))
                <div class="bg-white shadow-md rounded-xl p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Shipping Options</h2>
                    
                    <div class="space-y-3">
                        @foreach($availableCarriers as $key => $carrier)
                            <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition
                                      {{ $shipping_carrier === $key ? 'border-[#D4AF37] bg-yellow-50' : '' }}">
                                <div class="flex items-center space-x-3">
                                    <input type="radio" 
                                           wire:model.live="shipping_carrier" 
                                           value="{{ $key }}" 
                                           class="text-[#D4AF37] focus:ring-[#D4AF37]">
                                    
                                    <div>
                                        <span class="font-semibold">{{ $carrier['name'] }}</span>
                                        <p class="text-sm text-gray-500">
                                            @if(isset($carrier['vendors'][array_key_first($carrier['vendors'])]['delivery_days']))
                                                📅 {{ $carrier['vendors'][array_key_first($carrier['vendors'])]['delivery_days'] }} days
                                            @endif
                                        </p>
                                        
                                        {{-- Show vendor zones --}}
                                        @if(count($carrier['vendors']) > 0)
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                @foreach($carrier['vendors'] as $vendorId => $vendorShipping)
                                                    @if(isset($groups[$vendorId]))
                                                        <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded">
                                                            {{ $groups[$vendorId]['vendor']->store_name }}: {{ $vendorShipping['zone'] ?? 'Standard' }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <span class="font-bold text-lg">
                                        ${{ number_format($carrier['total_cost_usd'] ?? 0, 2) }}
                                    </span>
                                    <p class="text-sm text-gray-500">Shipping cost</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    
                    @error('shipping_carrier')<p class="text-red-500 text-sm mt-2">{{ $message }}</p>@enderror
                </div>
            @endif
            
            {{-- PAYMENT METHODS --}}
            <div class="bg-white shadow-md rounded-xl p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Payment Method *</h2>
                
                {{-- Show warning for multiple vendors --}}
                @if($has_multiple_vendors)
                    <div class="mb-4 p-4 bg-yellow-50 border border-yellow-300 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                            <p class="text-yellow-700">
                                <strong>Multiple Vendors Detected:</strong> Only Cash on Delivery is available for orders with multiple vendors.
                            </p>
                        </div>
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="cursor-pointer border rounded-xl p-4 text-center hover:bg-gray-50 transition
                                {{ $payment_method === 'cod' ? 'border-[#D4AF37] bg-yellow-50' : '' }} {{ $has_multiple_vendors ? 'opacity-100' : '' }}">
                        <input type="radio" wire:model.live="payment_method" value="cod" class="hidden" {{ $has_multiple_vendors ? 'checked disabled' : '' }}>
                        <div class="font-semibold text-gray-700">💵 Cash on Delivery</div>
                        <p class="text-sm text-gray-500 mt-1">Pay when you receive</p>
                        @if($has_multiple_vendors)
                            <p class="text-xs text-green-600 mt-1">✓ Required for multi-vendor orders</p>
                        @endif
                    </label>
                    
                    <label class="cursor-pointer border rounded-xl p-4 text-center hover:bg-gray-50 transition
                                {{ $payment_method === 'stripe' ? 'border-[#D4AF37] bg-yellow-50' : '' }} {{ $has_multiple_vendors ? 'opacity-50 cursor-not-allowed bg-gray-100' : 'hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="payment_method" value="stripe" class="hidden" {{ $has_multiple_vendors ? 'disabled' : '' }}>
                        <div class="font-semibold text-gray-700">💳 Credit/Debit Card</div>
                        <p class="text-sm text-gray-500 mt-1">Secure payment via Stripe</p>
                        @if($has_multiple_vendors)
                            <p class="text-xs text-red-500 mt-1">✗ Not available for multi-vendor</p>
                        @endif
                    </label>
                    
                    <label class="cursor-pointer border rounded-xl p-4 text-center hover:bg-gray-50 transition
                                {{ $payment_method === 'om' ? 'border-[#D4AF37] bg-yellow-50' : '' }} {{ $has_multiple_vendors ? 'opacity-50 cursor-not-allowed bg-gray-100' : 'hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="payment_method" value="om" class="hidden" {{ ($has_multiple_vendors || $selectedCurrency != 'GNF') ? 'disabled' : '' }}>
                        <div class="font-semibold text-gray-700">📱 Orange Money</div>
                        <p class="text-sm text-gray-500 mt-1">Mobile money payment</p>
                        @if($has_multiple_vendors)
                            <p class="text-xs text-red-500 mt-1">✗ Not available for multi-vendor</p>
                        @endif
                        @if($selectedCurrency != 'GNF')
                            <p class="text-xs text-green-600 mt-1">✓ Required for GNF orders</p>
                        @endif
                    </label>
                </div>
                @error('payment_method')<p class="text-red-500 text-sm mt-2">{{ $message }}</p>@enderror
                
                {{-- ORANGE MONEY FIELDS --}}
                @if ($payment_method === 'om')
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-300 rounded-xl">
                        <p class="text-yellow-800 font-semibold mb-3">
                            Send payment to <strong>625170257</strong> and upload screenshot
                        </p>
                         {{-- Show the exact amount to pay --}}
                        @foreach($groups as $vendorId => $group)
                            <div class="mb-3 p-3 bg-white rounded-lg border">
                                <div class="text-center">
                                    <p class="text-sm text-gray-500">Send exactly:</p>
                                    <span class="font-bold text-2xl text-green-600">
                                        {{ number_format($group['total'], 2) }} GNF
                                    </span>
                                    <p class="text-sm text-gray-500 mt-1">
                                        (Entrer just le montant dans le champ mais la capture doit comporter les frais {{ number_format($group['total'] * 0.01, 2) }} GNF)
                                    </p>
                                </div>
                            </div>
                        @endforeach
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-gray-700">Amount Sent *</label>
                                <input type="number" wire:model="amount" min="1" step="0.01"
                                       class="w-full mt-1 p-3 border rounded-lg focus:ring-2 focus:ring-[#D4AF37]">
                                @error('amount')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                {{-- Simple amount validation --}}
                                @if($amount > 0 && !empty($groups))
                                    @php
                                        $vendorGroup = reset($groups); // Get first (and only) vendor
                                        $exactAmount = $vendorGroup['total'];
                                    @endphp
                                    
                                    @if(number_format($amount, 2) == number_format($exactAmount, 2))
                                        <p class="text-green-600 text-sm mt-2">
                                            ✅ Amount matches exactly!
                                        </p>
                                    @else
                                        <p class="text-red-500 text-sm mt-2">
                                            ❌ Amount must be exactly {{ number_format($exactAmount, 2) }} GNF
                                        </p>
                                    @endif
                                @endif
                            </div>
                            
                            <div>
                                <label class="text-gray-700">Payment Screenshot *</label>
                                <input type="file" wire:model="image" accept="image/*"
                                       class="w-full mt-1 p-3 border rounded-lg focus:ring-2 focus:ring-[#D4AF37]">
                                @error('image')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                
                                <div wire:loading wire:target="image" class="text-gray-500 mt-1">
                                    Uploading...
                                </div>
                            </div>
                        </div>
                        
                        @if ($image)
                            <div class="mt-3">
                                <img src="{{ $image->temporaryUrl() }}" 
                                     class="w-48 h-48 object-cover rounded-lg border">
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            
            {{-- PLACE ORDER BUTTON --}}
            <button wire:click="placeOrder" 
                    wire:loading.attr="disabled"
                    wire:target="placeOrder"
                    class="w-full bg-[#D4AF37] hover:bg-[#C9A227] text-white text-lg font-semibold p-4 rounded-xl shadow-md transition disabled:opacity-50">
                <span wire:loading.remove wire:target="placeOrder">
                    ✅ Place Order ({{ $selected_count }} items)
                </span>
                <span wire:loading wire:target="placeOrder">
                    ⏳ Processing Order...
                </span>
            </button>
        </div>
        
        {{-- RIGHT COLUMN — ORDER SUMMARY --}}
        <div class="lg:col-span-4">
            <div class="bg-white shadow-md rounded-xl p-6 sticky top-10">
                <h2 class="text-xl font-bold text-gray-800 mb-3">
                    Order Summary ({{ $selected_count }} items)
                </h2>
                
                {{-- Display by vendor --}}
                @foreach($groups as $vendorId => $group)
                    <div class="mb-4 pb-4 border-b last:border-b-0">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="font-semibold text-gray-700">
                                🏬 {{ $group['vendor']->store_name ?? 'Vendor' }}
                            </h3>
                            @if($group['zone'] !== 'Unknown')
                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                    {{ $group['zone'] }}
                                </span>
                            @endif
                        </div>
                        
                        {{-- Items list --}}
                        <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                            @foreach($group['items'] as $item)
                                <div class="flex justify-between text-sm">
                                    <div class="flex-1">
                                        <p class="font-medium">{{ $item['product_name'] ?? 'Product' }}</p>
                                        @if(!empty($item['selected_variations']) || !empty($item['variation_note']))
                                            <div class="text-xs text-gray-500 mt-1">
                                                @if(!empty($item['selected_variations']))
                                                    @foreach($item['selected_variations'] as $key => $value)
                                                        <span>{{ ucfirst($key) }}: {{ $value }}</span>
                                                        @if(!$loop->last) • @endif
                                                    @endforeach
                                                @endif
                                                @if(!empty($item['variation_note']))
                                                    <div class="text-blue-600">📝 {{ $item['variation_note'] }}</div>
                                                @endif
                                            </div>
                                        @endif
                                        <p class="text-gray-500">× {{ $item['quantity'] }}</p>
                                        
                                        {{-- Weight/Volume info if available --}}
                                        @if($group['weight'] > 0)
                                            <p class="text-xs text-gray-400">
                                                📦 {{ number_format($group['weight'], 2) }} kg
                                                @if($group['cbm'] > 0)
                                                     • {{ number_format($group['cbm'], 3) }} m³
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p>{{ number_format($item['unit_amount'], 2) }} {{ $group['currency'] }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ number_format($item['total_amount'], 2) }} {{ $group['currency'] }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Vendor subtotal with shipping --}}
                        <div class="mt-3 pt-3 border-t">
                            <div class="flex justify-between text-sm">
                                <span>Subtotal:</span>
                                <span>{{ number_format($group['subtotal_usd'] / $group['rate_to_usd'] , 2) }} {{ $group['currency'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm mt-1">
                                <span>
                                    Shipping:
                                    <span class="text-xs text-gray-500">
                                        ({{ $selectedCarrier['name'] ?? 'Standard' }})
                                    </span>
                                </span>
                                <span>{{ number_format($group['shipping_usd'] / $group['rate_to_usd'], 2) }} {{ $group['currency'] }}</span>
                            </div>
                            <div class="flex justify-between font-semibold mt-2 text-[#D4AF37]">
                                <span>Total {{ $group['currency'] }}:</span>
                                <span>{{ number_format($group['total'], 2) }} {{ $group['currency'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
                
                {{-- Grand Total --}}
                <div class="mt-4 pt-4 border-t">
                    <div class="flex justify-between text-sm mb-1">
                        <span>Items Subtotal:</span>
                        <span>${{ number_format($subtotal_usd, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>Shipping:</span>
                        <span>${{ number_format($total_shipping_usd, 2) }}</span>
                    </div>
                    
                    @if($selectedCarrier && isset($selectedCarrier['delivery_days']))
                        <div class="flex justify-between text-sm mb-1 text-green-600">
                            <span>Estimated Delivery:</span>
                            <span>{{ $selectedCarrier['delivery_days'] ?? 3-5 }} business days</span>
                        </div>
                    @endif
                    
                    <div class="flex justify-between font-bold text-lg text-gray-900 mt-2 pt-2 border-t">
                        <span>Total USD:</span>
                        <span>${{ number_format($grand_total_usd, 2) }}</span>
                    </div>
                    
                    @if($has_shipping_calculated)
                        <p class="text-xs text-green-600 mt-2">
                            ✅ Shipping calculated based on weight and destination
                        </p>
                    @else
                        <p class="text-xs text-yellow-600 mt-2">
                            ⚠️ Enter address to calculate shipping
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript for geolocation --}}
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('get-browser-location', () => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        @this.set('latitude', position.coords.latitude.toFixed(6));
                        @this.set('longitude', position.coords.longitude.toFixed(6));
                    },
                    (error) => {
                        console.error('Geolocation error:', error);
                        alert('Unable to get location. Please enter manually.');
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        });
    });
</script>
