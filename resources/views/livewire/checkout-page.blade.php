<div>{{-- livewire-root : Livewire n'accepte qu'un seul element racine --}}
<div class="bg-gray-50 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- LEFT COLUMN — SHIPPING + PAYMENT FORM --}}
        <div class="lg:col-span-8 space-y-6">
            {{-- FLASH MESSAGES --}}
            @if(session()->has('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                    {{ session('error') }}
                </div>
            @endif

            @if(session()->has('shipping-success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
                    {{ session('shipping-success') }}
                </div>
            @endif

            @if(session()->has('shipping-error'))
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl">
                    {{ session('shipping-error') }}
                </div>
            @endif

            {{-- SHIPPING ADDRESS FORM --}}
            <div class="bg-white shadow-sm border border-gray-100 rounded-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-full bg-[#F5E6B3] text-[#96731A] flex items-center justify-center text-sm">
                        <i class="fa-solid fa-location-dot"></i>
                    </span>
                    Adresse de livraison
                </h2>
                @auth
                {{-- Was a second full white card (own shadow, own padding) nested inside
                     this one. An inset panel reads as part of the form, not a card
                     stacked in a card. --}}
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold text-gray-800">Adresses enregistrées</h3>

                        <button type="button"
                                wire:click="clearSelectedAddress"
                                class="text-sm px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-700 hover:border-[#D4AF37] hover:text-[#96731A] transition-colors">
                            Utiliser une nouvelle adresse
                        </button>
                    </div>

                    @if(!empty($savedAddresses))
                        <div class="relative">
                            <select
                                wire:model="selected_address_id"
                                wire:change="applyAddress($event.target.value)"
                                class="w-full appearance-none rounded-xl border border-[#E6D8A3] bg-gradient-to-r from-[#FFF7DF] to-white px-4 py-3 pr-10 text-gray-800 shadow-sm transition focus:border-[#D4AF37] focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/40">
                                <option value="">Choisir une adresse enregistrée</option>
                                @foreach($savedAddresses as $addr)
                                    <option value="{{ $addr['id'] }}">
                                        {{ $addr['first_name'] }} {{ $addr['last_name'] }} — {{ $addr['street_address'] }}, {{ $addr['city'] }}
                                        @if(!empty($addr['is_default'])) (Par défaut) @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[#8a6a00]">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>

                        <label class="mt-3 flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="save_address" class="rounded border-gray-300 accent-[#D4AF37] text-[#D4AF37] focus:ring-[#D4AF37]/40">
                            <span class="text-sm text-gray-700">Enregistrer la nouvelle adresse dans mon profil</span>
                        </label>
                    @else
                        <p class="text-sm text-gray-600">Aucune adresse enregistrée pour le moment. Remplissez le formulaire ci-dessous pour en ajouter une.</p>
                        <label class="mt-3 flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="save_address" class="rounded border-gray-300 accent-[#D4AF37] text-[#D4AF37] focus:ring-[#D4AF37]/40" checked>
                            <span class="text-sm text-gray-700">Enregistrer cette adresse dans mon profil</span>
                        </label>
                    @endif
                </div>
                @endauth

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Prénom *</label>
                        <input wire:model.blur="first_name" type="text"
                               class="mt-1 w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        @error('first_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Nom *</label>
                        <input wire:model.blur="last_name" type="text"
                               class="mt-1 w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        @error('last_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-sm font-medium text-gray-700">Téléphone *</label>
                    <input wire:model.blur="phone" type="tel"
                           class="mt-1 w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                    @error('phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="mt-4">
                    <label class="text-sm font-medium text-gray-700">Adresse *</label>
                    <textarea wire:model.blur="street_address" rows="2"
                              class="mt-1 w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent"></textarea>
                    @error('street_address')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Ville *</label>
                        <input wire:model.blur="city" type="text"
                               class="mt-1 w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        @error('city')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Région *</label>
                        <input wire:model.blur="state" type="text"
                               class="mt-1 w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        @error('state')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Code postal</label>
                        <input wire:model.blur="zip_code" type="text"
                               class="mt-1 w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        @error('zip_code')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    @if($needs_locality)
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-gray-700">Localité de livraison @if($has_localities_for_country) * @endif</label>
                            @if($has_localities_for_country)
                                <select wire:model.live="locality_id"
                                        class="mt-1 w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                                    <option value="">— Choisissez votre localité —</option>
                                    @foreach($localities as $localityId => $localityName)
                                        <option value="{{ $localityId }}">{{ $localityName }}</option>
                                    @endforeach
                                </select>
                                <p class="text-gray-500 text-sm mt-1">
                                    Les frais de livraison sont calculés automatiquement à partir de votre localité.
                                </p>
                            @else
                                {{-- No city catalogued for this country yet: the vendor's own
                                     default_shipping_amount covers the quote instead — see
                                     LocalityShippingCalculator::calculateVendorShipping(). --}}
                                <p class="mt-1 w-full p-3 border rounded-lg bg-gray-50 text-gray-500 text-sm">
                                    Aucune localité disponible pour {{ $country }} pour le moment. Les frais de
                                    livraison standard du vendeur s'appliqueront.
                                </p>
                            @endif
                            @error('locality_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <label class="text-sm font-medium text-gray-700">Pays *</label>
                    {{-- Only the visible labels are French. The value="" attributes are
                         matched against the shipping-zone and locality data by their
                         English names, so they stay exactly as they were. --}}
                    <select wire:model.blur="country"
                            class="mt-1 w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-transparent">
                        <option value="Guinea">Guinée</option>
                        <option value="Senegal">Sénégal</option>
                        <option value="Ivory Coast">Côte d'Ivoire</option>
                        <option value="Mali">Mali</option>
                        <option value="France">France</option>
                        <option value="United States">États-Unis</option>
                        <option value="Canada">Canada</option>
                        <option value="United Kingdom">Royaume-Uni</option>
                        <option value="Other">Autre</option>
                    </select>
                    @error('country')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- GEOLOCATION --}}
                <div class="mt-6 border-t border-gray-100 pt-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Localisation (facultatif)</h3>
                            <p class="text-sm text-gray-500">Pour un calcul précis de la zone de livraison</p>
                        </div>
                        <button type="button" wire:click="getLocation"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium bg-white border border-[#D4AF37] text-[#96731A] rounded-lg hover:bg-[#FDF6E3] transition-colors disabled:opacity-50">
                            <span wire:loading.remove wire:target="getLocation"><i class="fa-solid fa-location-crosshairs mr-1.5"></i> Ma position</span>
                            <span wire:loading wire:target="getLocation"><i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Localisation en cours…</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Latitude</label>
                            <input wire:model.blur="latitude" type="number" step="0.000001"
                                   class="mt-1 w-full p-3 border border-gray-200 rounded-lg" placeholder="ex. 9.945587">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Longitude</label>
                            <input wire:model.blur="longitude" type="number" step="0.000001"
                                   class="mt-1 w-full p-3 border border-gray-200 rounded-lg" placeholder="ex. -9.696677">
                        </div>
                    </div>

                    @if($has_location)
                        <p class="text-sm text-green-600 mt-2">
                            <i class="fa-solid fa-circle-check mr-1"></i> Position détectée. La livraison sera calculée avec précision.
                        </p>
                    @endif
                </div>

                {{-- CALCULATE SHIPPING BUTTON --}}
                {{-- Was bg-blue-600: the one blue button on a gold-branded site, and
                     it sat right above the gold "Place Order" as if it were the more
                     important of the two. --}}
                <div class="mt-6">
                    <button type="button" wire:click="calculateShipping"
                            wire:loading.attr="disabled"
                            wire:target="calculateShipping"
                            class="w-full bg-white border-2 border-[#D4AF37] text-[#96731A] hover:bg-[#FDF6E3] font-semibold py-3 px-4 rounded-lg transition-colors disabled:opacity-50">
                        <span wire:loading.remove wire:target="calculateShipping">
                            <i class="fa-solid fa-truck-fast mr-1.5"></i> Calculer les frais de livraison
                        </span>
                        <span wire:loading wire:target="calculateShipping">
                            <i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Calcul en cours…
                        </span>
                    </button>
                </div>
            </div>

            {{-- SHIPPING OPTIONS --}}
            @if($has_shipping_calculated && !empty($availableCarriers) && $carrier_choice_applies)
                <div class="bg-white shadow-sm border border-gray-100 rounded-xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-full bg-[#F5E6B3] text-[#96731A] flex items-center justify-center text-sm">
                            <i class="fa-solid fa-truck"></i>
                        </span>
                        Options de livraison
                    </h2>

                    <div class="space-y-3">
                        @foreach($availableCarriers as $key => $carrier)
                            <label class="flex items-center justify-between p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition
                                      {{ $shipping_carrier === $key ? '!border-[#D4AF37] bg-[#FDF6E3]' : '' }}">
                                <div class="flex items-center space-x-3">
                                    <input type="radio"
                                           wire:model.live="shipping_carrier"
                                           value="{{ $key }}"
                                           class="text-[#D4AF37] focus:ring-[#D4AF37]">

                                    <div>
                                        <span class="font-semibold">{{ $carrier['name'] }}</span>
                                        <p class="text-sm text-gray-500">
                                            @if(isset($carrier['vendors'][array_key_first($carrier['vendors'])]['delivery_days']))
                                                <i class="fa-regular fa-calendar mr-1"></i> {{ $carrier['vendors'][array_key_first($carrier['vendors'])]['delivery_days'] }} jours
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
                                    <span class="font-bold text-lg text-[#96731A]">
                                        ${{ number_format($carrier['total_cost_usd'] ?? 0, 2) }}
                                    </span>
                                    <p class="text-sm text-gray-500">Frais de livraison</p>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @error('shipping_carrier')<p class="text-red-500 text-sm mt-2">{{ $message }}</p>@enderror
                </div>
            @endif

            {{-- PAYMENT METHODS --}}
            <div class="bg-white shadow-sm border border-gray-100 rounded-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-full bg-[#F5E6B3] text-[#96731A] flex items-center justify-center text-sm">
                        <i class="fa-solid fa-credit-card"></i>
                    </span>
                    Mode de paiement *
                </h2>

                {{-- Show warning for multiple vendors --}}
                @if($has_multiple_vendors)
                    <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-amber-500 mr-2"></i>
                            <p class="text-amber-800">
                                <strong>Plusieurs vendeurs détectés :</strong> seul le paiement à la livraison est disponible pour les commandes comportant plusieurs vendeurs.
                            </p>
                        </div>
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="cursor-pointer border border-gray-200 rounded-xl p-4 text-center hover:bg-gray-50 transition
                                {{ $payment_method === 'cod' ? '!border-[#D4AF37] bg-[#FDF6E3]' : '' }} {{ $has_multiple_vendors ? 'opacity-100' : '' }}">
                        <input type="radio" wire:model.live="payment_method" value="cod" class="hidden" {{ $has_multiple_vendors ? 'checked disabled' : '' }}>
                        <div class="text-[#96731A] text-xl mb-1"><i class="fa-solid fa-money-bill-wave"></i></div>
                        <div class="font-semibold text-gray-700">Paiement à la livraison</div>
                        <p class="text-sm text-gray-500 mt-1">Payez à la réception</p>
                        @if($has_multiple_vendors)
                            <p class="text-xs text-green-600 mt-1"><i class="fa-solid fa-check"></i> Obligatoire pour les commandes multi-vendeurs</p>
                        @endif
                    </label>

                    <label class="cursor-pointer border border-gray-200 rounded-xl p-4 text-center hover:bg-gray-50 transition
                                {{ $payment_method === 'stripe' ? '!border-[#D4AF37] bg-[#FDF6E3]' : '' }} {{ $has_multiple_vendors ? 'opacity-50 cursor-not-allowed bg-gray-100' : 'hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="payment_method" value="stripe" class="hidden" {{ $has_multiple_vendors ? 'disabled' : '' }}>
                        <div class="text-[#96731A] text-xl mb-1"><i class="fa-regular fa-credit-card"></i></div>
                        <div class="font-semibold text-gray-700">Carte bancaire</div>
                        <p class="text-sm text-gray-500 mt-1">Paiement sécurisé via Stripe</p>
                        @if($has_multiple_vendors)
                            <p class="text-xs text-red-500 mt-1"><i class="fa-solid fa-xmark"></i> Indisponible pour plusieurs vendeurs</p>
                        @endif
                    </label>

                    <label class="cursor-pointer border border-gray-200 rounded-xl p-4 text-center hover:bg-gray-50 transition
                                {{ $payment_method === 'om' ? '!border-[#D4AF37] bg-[#FDF6E3]' : '' }} {{ $has_multiple_vendors ? 'opacity-50 cursor-not-allowed bg-gray-100' : 'hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="payment_method" value="om" class="hidden" {{ ($has_multiple_vendors || $selectedCurrency != 'GNF') ? 'disabled' : '' }}>
                        <div class="text-[#96731A] text-xl mb-1"><i class="fa-solid fa-mobile-screen-button"></i></div>
                        <div class="font-semibold text-gray-700">Orange Money</div>
                        <p class="text-sm text-gray-500 mt-1">Paiement mobile</p>
                        @if($has_multiple_vendors)
                            <p class="text-xs text-red-500 mt-1"><i class="fa-solid fa-xmark"></i> Indisponible pour plusieurs vendeurs</p>
                        @endif
                        @if($selectedCurrency != 'GNF')
                            <p class="text-xs text-green-600 mt-1"><i class="fa-solid fa-check"></i> Obligatoire pour les commandes en GNF</p>
                        @endif
                    </label>
                </div>
                @error('payment_method')<p class="text-red-500 text-sm mt-2">{{ $message }}</p>@enderror

                {{-- ORANGE MONEY FIELDS --}}
                @if ($payment_method === 'om')
                    <div class="mt-6 p-4 bg-[#FDF6E3] border border-[#E6D8A3] rounded-xl">
                        <p class="text-[#6B5410] font-semibold mb-3">
                            Envoyez le paiement au <strong>625170257</strong> puis téléversez la capture d'écran
                        </p>
                         {{-- Show the exact amount to pay --}}
                        @foreach($groups as $vendorId => $group)
                            <div class="mb-3 p-3 bg-white rounded-lg border border-gray-200">
                                <div class="text-center">
                                    <p class="text-sm text-gray-500">Envoyez exactement :</p>
                                    <span class="font-bold text-2xl text-green-600">
                                        {{ number_format($group['total'], 2) }} GNF
                                    </span>
                                    <p class="text-sm text-gray-500 mt-1">
                                        (Saisissez uniquement le montant dans le champ, mais la capture doit inclure les frais de {{ number_format($group['total'] * 0.01, 2) }} GNF)
                                    </p>
                                </div>
                            </div>
                        @endforeach
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Montant envoyé *</label>
                                <input type="number" wire:model="amount" min="1" step="0.01"
                                       class="w-full mt-1 p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-[#D4AF37]">
                                @error('amount')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                                {{-- Simple amount validation --}}
                                @if($amount > 0 && !empty($groups))
                                    @php
                                        $vendorGroup = reset($groups); // Get first (and only) vendor
                                        $exactAmount = $vendorGroup['total'];
                                    @endphp

                                    @if(number_format($amount, 2) == number_format($exactAmount, 2))
                                        <p class="text-green-600 text-sm mt-2">
                                            <i class="fa-solid fa-circle-check mr-1"></i> Le montant correspond exactement !
                                        </p>
                                    @else
                                        <p class="text-red-500 text-sm mt-2">
                                            <i class="fa-solid fa-circle-xmark mr-1"></i> Le montant doit être exactement de {{ number_format($exactAmount, 2) }} GNF
                                        </p>
                                    @endif
                                @endif
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-700">Capture du paiement *</label>
                                <input type="file" wire:model="image" accept="image/*"
                                       class="w-full mt-1 p-3 border border-gray-200 bg-white rounded-lg focus:ring-2 focus:ring-[#D4AF37]">
                                @error('image')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror

                                <div wire:loading wire:target="image" class="text-gray-500 mt-1">
                                    Téléversement en cours…
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
                    class="w-full bg-[#D4AF37] hover:bg-[#C9A227] text-white text-lg font-semibold p-4 rounded-xl shadow-sm hover:shadow-md transition-all disabled:opacity-50">
                <span wire:loading.remove wire:target="placeOrder">
                    <i class="fa-solid fa-lock mr-2 text-base"></i> Passer la commande ({{ $selected_count }} article(s))
                </span>
                <span wire:loading wire:target="placeOrder">
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i> Traitement de la commande…
                </span>
            </button>
        </div>

        {{-- RIGHT COLUMN — ORDER SUMMARY --}}
        <div class="lg:col-span-4">
            <div class="bg-white shadow-sm border border-gray-100 rounded-xl p-6 sticky top-28">
                <h2 class="text-lg font-bold text-gray-800 mb-4 pb-4 border-b border-gray-100">
                    Récapitulatif ({{ $selected_count }} article(s))
                </h2>

                {{-- Display by vendor --}}
                @foreach($groups as $vendorId => $group)
                    <div class="mb-4 pb-4 border-b border-gray-100 last:border-b-0">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                                <i class="fa-solid fa-store text-[#D4AF37]"></i>
                                {{ $group['vendor']->store_name ?? 'Vendeur' }}
                            </h3>
                            @if($group['zone'] !== 'Unknown')
                                <span class="px-2 py-1 text-xs bg-[#F5E6B3]/60 text-[#6B5410] rounded">
                                    {{ $group['zone'] }}
                                </span>
                            @endif
                        </div>

                        {{-- Items list --}}
                        <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                            @foreach($group['items'] as $item)
                                <div class="flex justify-between text-sm">
                                    <div class="flex-1">
                                        <p class="font-medium">{{ $item['product_name'] ?? 'Produit' }}</p>
                                        @if(!empty($item['selected_variations']) || !empty($item['variation_note']))
                                            <div class="text-xs text-gray-500 mt-1">
                                                @if(!empty($item['selected_variations']))
                                                    @foreach($item['selected_variations'] as $key => $value)
                                                        <span>{{ ucfirst($key) }}: {{ $value }}</span>
                                                        @if(!$loop->last) • @endif
                                                    @endforeach
                                                @endif
                                                @if(!empty($item['variation_note']))
                                                    <div class="text-[#96731A]"><i class="fa-regular fa-note-sticky mr-1"></i> {{ $item['variation_note'] }}</div>
                                                @endif
                                            </div>
                                        @endif
                                        <p class="text-gray-500">× {{ $item['quantity'] }}</p>

                                        {{-- Weight/Volume info if available --}}
                                        @if($group['weight'] > 0)
                                            <p class="text-xs text-gray-400">
                                                <i class="fa-solid fa-box mr-1"></i> {{ number_format($group['weight'], 2) }} kg
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
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex justify-between text-sm">
                                <span>Sous-total :</span>
                                <span>{{ number_format($group['subtotal_usd'] / $group['rate_to_usd'] , 2) }} {{ $group['currency'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm mt-1">
                                <span>
                                    Livraison :
                                    <span class="text-xs text-gray-500">
                                        ({{ $selectedCarrier['name'] ?? 'Standard' }})
                                    </span>
                                </span>
                                <span>{{ number_format($group['shipping_usd'] / $group['rate_to_usd'], 2) }} {{ $group['currency'] }}</span>
                            </div>
                            <div class="flex justify-between font-semibold mt-2 text-[#96731A]">
                                <span>Total {{ $group['currency'] }} :</span>
                                <span>{{ number_format($group['total'], 2) }} {{ $group['currency'] }}</span>
                            </div>

                            {{-- Négociation, par boutique. Le prix se discute sur le panier
                                 d'un vendeur, pas sur une ligne : c'est lui qui consent la
                                 remise, et order_items ne permet pas de repricer une ligne
                                 depuis son annonce d'origine. --}}
                            @if($negotiatingVendorId === $vendorId)
                                <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                    <p class="text-xs text-gray-600 mb-2">
                                        Proposez votre prix pour ce panier. Le vendeur répondra,
                                        et vous paierez seulement si vous êtes d'accord.
                                    </p>

                                    <label class="block text-xs font-medium text-gray-700">
                                        Votre prix souhaité ({{ $group['currency'] }})
                                    </label>
                                    {{-- « border-gray-300 » sans « border » ne pose aucune bordure :
                                         Tailwind y règle la couleur, pas l'épaisseur. Ces deux champs
                                         sortaient donc sans bordure ni marge intérieure, au milieu
                                         d'une page où tous les autres portent « p-3 border rounded-lg ».
                                         Mêmes classes qu'eux désormais. --}}
                                    <input type="number" step="0.01" min="0"
                                           wire:model="negotiationTargetPrice"
                                           placeholder="{{ number_format($group['total'], 2, '.', '') }}"
                                           class="w-full mt-1 p-3 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37] focus:outline-none">
                                    @error('negotiationTargetPrice')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror

                                    <label class="block text-xs font-medium text-gray-700 mt-2">
                                        Votre message
                                    </label>
                                    <textarea wire:model="negotiationMessage" rows="3"
                                              placeholder="Bonjour, seriez-vous d'accord pour…"
                                              class="w-full mt-1 p-3 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37] focus:outline-none"></textarea>
                                    @error('negotiationMessage')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror

                                    <div class="flex gap-2 mt-3">
                                        <button type="button"
                                                wire:click="startNegotiation"
                                                wire:loading.attr="disabled"
                                                wire:target="startNegotiation"
                                                class="flex-1 bg-[#D4AF37] text-white text-sm font-medium py-2 rounded-lg disabled:opacity-50">
                                            <span wire:loading.remove wire:target="startNegotiation">Envoyer au vendeur</span>
                                            <span wire:loading wire:target="startNegotiation">Envoi…</span>
                                        </button>
                                        <button type="button" wire:click="cancelNegotiation"
                                                class="px-3 text-sm text-gray-600 border border-gray-300 rounded-lg">
                                            Annuler
                                        </button>
                                    </div>
                                </div>
                            @else
                                <button type="button" wire:click="openNegotiation({{ $vendorId }})"
                                        class="w-full mt-3 text-sm text-[#D4AF37] border border-[#D4AF37] rounded-lg py-2 hover:bg-amber-50 transition">
                                    Discuter le prix
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Grand Total --}}
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="flex justify-between text-sm mb-1">
                        <span>Sous-total articles :</span>
                        <span>${{ number_format($subtotal_usd, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>Livraison :</span>
                        <span>${{ number_format($total_shipping_usd, 2) }}</span>
                    </div>

                    @if($selectedCarrier && isset($selectedCarrier['delivery_days']))
                        <div class="flex justify-between text-sm mb-1 text-green-600">
                            <span>Livraison estimée :</span>
                            <span>{{ $selectedCarrier['delivery_days'] ?? 3-5 }} jours ouvrés</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-baseline font-bold text-lg text-gray-900 mt-2 pt-3 border-t border-gray-100">
                        <span>Total USD :</span>
                        <span class="text-[#96731A] text-xl">${{ number_format($grand_total_usd, 2) }}</span>
                    </div>

                    @if($has_shipping_calculated)
                        <p class="text-xs text-green-600 mt-2">
                            <i class="fa-solid fa-circle-check mr-1"></i> Livraison calculée selon le poids et la destination
                        </p>
                    @else
                        <p class="text-xs text-amber-600 mt-2">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i> Saisissez votre adresse pour calculer la livraison
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
                        alert('Impossible d\'obtenir votre position. Veuillez la saisir manuellement.');
                    }
                );
            } else {
                alert('La géolocalisation n\'est pas prise en charge par votre navigateur.');
            }
        });
    });
</script>

</div>
