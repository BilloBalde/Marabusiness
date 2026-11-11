<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @if(session()->has('success'))
        <div class="mb-4 p-3.5 bg-green-50 text-green-700 border border-green-200 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mb-4 p-3.5 bg-red-50 text-red-700 border border-red-200 rounded-xl text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Devenir vendeur</h1>
        <p class="text-gray-500 mt-2">Merci de lire et d'accepter les conditions avant de soumettre votre profil boutique.</p>
        <div class="w-16 h-1 bg-[#D4AF37] mt-4 rounded-full"></div>
    </div>

    {{-- TERMS MODAL --}}
    @if($showTermsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-semibold text-lg text-gray-900">Conditions et contrat de commission</h2>
                    <a href="{{ route('vendors.list') }}" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                </div>

                <div class="p-6 space-y-4 max-h-[65vh] overflow-y-auto">
                    <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 text-sm text-gray-700 space-y-2">
                        <p class="font-semibold text-gray-900">Règles de la marketplace</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Les informations de votre boutique doivent être exactes.</li>
                            <li>Les produits doivent être authentiques et légaux à la vente.</li>
                            <li>Les commandes doivent être honorées dans les délais.</li>
                            <li>Des manquements répétés peuvent entraîner une suspension.</li>
                        </ul>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 border border-gray-100 text-sm text-gray-700 space-y-2">
                        <p class="font-semibold text-gray-900">Contrat de commission</p>
                        <p>
                            En rejoignant la plateforme, vous acceptez qu'une commission soit prélevée sur chaque
                            commande (pourcentage ou frais fixes selon votre formule et votre moyen de paiement).
                        </p>
                    </div>

                    <label class="flex items-start gap-2.5 text-sm text-gray-700">
                        <input type="checkbox" class="mt-1 rounded border-gray-300 text-[#D4AF37] focus:ring-[#D4AF37]"
                               wire:model="acceptedTerms">
                        <span>
                            J'ai lu et j'accepte les conditions et le contrat de commission.
                        </span>
                    </label>

                    @error('acceptedTerms')
                        <div class="text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex gap-2 justify-end">
                    <a href="{{ route('vendors.list') }}"
                       class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-white transition-colors">
                        Annuler
                    </a>
                    <button wire:click="acceptTerms"
                            class="px-4 py-2 rounded-lg bg-[#D4AF37] text-white text-sm font-semibold hover:bg-[#c9a12f] transition-colors">
                        J'accepte — Continuer
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- FORM --}}
    @if(!$showTermsModal)
        <div class="space-y-6">

            {{-- If guest: create user first --}}
            @guest
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Créer votre compte</h3>
                    <p class="text-sm text-gray-500 mb-5">Cet accès vous permettra de gérer votre boutique.</p>

                    @php
                        $guestField = 'w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/40 transition';
                        $guestLabel = 'block text-sm font-medium text-gray-700 mb-1';
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $guestLabel }}">Nom complet</label>
                            <input type="text" wire:model="name" class="{{ $guestField }}">
                            @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="{{ $guestLabel }}">Email</label>
                            <input type="email" wire:model="email" class="{{ $guestField }}">
                            @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="{{ $guestLabel }}">Mot de passe</label>
                            <input type="password" wire:model="password" class="{{ $guestField }}">
                            @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="{{ $guestLabel }}">Confirmer le mot de passe</label>
                            <input type="password" wire:model="password_confirmation" class="{{ $guestField }}">
                        </div>
                    </div>
                </div>
            @endguest

            @php
                $inputClass = 'w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900
                            placeholder:text-gray-400 shadow-sm
                            focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/40
                            transition';
                $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
                $errorClass = 'mt-1 text-sm text-red-600';
            @endphp

            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Profil de la boutique</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Ces informations apparaîtront sur votre page boutique publique.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                    {{-- Store name --}}
                    <div class="md:col-span-6">
                        <label class="{{ $labelClass }}">Nom de la boutique</label>
                        <input type="text" wire:model="store_name" class="{{ $inputClass }}" placeholder="ex. Mara Électronique">
                        @error('store_name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Currency --}}
                    <div class="md:col-span-6">
                        <label class="{{ $labelClass }}">Devise</label>
                        <select wire:model="currency_id" class="{{ $inputClass }}">
                            <option value="">Choisir une devise</option>
                            @foreach($currencies as $c)
                                <option value="{{ $c->id }}">{{ $c->code }}</option>
                            @endforeach
                        </select>
                        @error('currency_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-12">
                        <label class="{{ $labelClass }}">Description</label>
                        <textarea wire:model="description" rows="4" class="{{ $inputClass }}"
                                placeholder="Ce que vous vendez, vos délais de livraison, etc."></textarea>
                        @error('description') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Country --}}
                    <div class="md:col-span-4">
                        <label class="{{ $labelClass }}">Pays</label>
                        <input type="text" wire:model="country" class="{{ $inputClass }}" placeholder="Guinée">
                        @error('country') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- City --}}
                    <div class="md:col-span-4">
                        <label class="{{ $labelClass }}">Ville</label>
                        <input type="text" wire:model="city" class="{{ $inputClass }}" placeholder="Conakry">
                        @error('city') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Zip --}}
                    <div class="md:col-span-4">
                        <label class="{{ $labelClass }}">Code postal</label>
                        <input type="text" wire:model="zip_code" class="{{ $inputClass }}" placeholder="ex. 00000">
                        @error('zip_code') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Address --}}
                    <div class="md:col-span-8">
                        <label class="{{ $labelClass }}">Adresse</label>
                        <input type="text" wire:model="address" class="{{ $inputClass }}" placeholder="Rue, immeuble, quartier...">
                        @error('address') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- State --}}
                    <div class="md:col-span-4">
                        <label class="{{ $labelClass }}">Région</label>
                        <input type="text" wire:model="state" class="{{ $inputClass }}" placeholder="Région">
                        @error('state') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- Store Logo --}}
                    <div class="md:col-span-12">
                        <label class="{{ $labelClass }}">Logo de la boutique</label>

                        <div class="mt-2 flex flex-col sm:flex-row sm:items-center gap-4">
                            {{-- Preview --}}
                            <div class="flex items-center gap-3">
                                @if ($logo)
                                    <img src="{{ $logo->temporaryUrl() }}"
                                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover border border-gray-200 shadow-sm">
                                @else
                                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl border border-dashed border-gray-300
                                                flex items-center justify-center text-gray-400 text-xs sm:text-sm bg-gray-50">
                                        Logo
                                    </div>
                                @endif

                                <div>
                                    <p class="text-sm font-medium text-gray-700">Ajouter un logo</p>
                                    <p class="text-xs text-gray-500">PNG, JPG, WEBP — 2 Mo max</p>
                                </div>
                            </div>

                            {{-- File input (styled wrapper) --}}
                            <label class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-300
                                        bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition w-full sm:w-auto">
                                Choisir un fichier
                                <input type="file"
                                    wire:model="logo"
                                    accept="image/*"
                                    class="hidden">
                            </label>
                        </div>

                        @error('logo')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('vendors.list') }}" class="px-5 py-2.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                    Annuler
                </a>
                <button wire:click="submit"
                        class="px-5 py-2.5 rounded-lg bg-[#D4AF37] text-white text-sm font-semibold hover:bg-[#c9a12f] transition-colors">
                    Envoyer ma candidature
                </button>
            </div>
        </div>
    @endif
</div>
