<div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @include('livewire.partials.nav-header', ['tileContent' => 'Mes adresses', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])

    <div class="flex items-center justify-between mb-8 flex-wrap gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Mes adresses</h1>
            <div class="w-16 h-1 bg-[#D4AF37] mt-4 rounded-full"></div>
        </div>
        @if ($editingId)
            <button wire:click="resetForm"
                    class="inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-plus mr-2"></i> Nouvelle adresse
            </button>
        @endif
    </div>

    @if (session('success'))
        <div class="mb-6 p-3.5 rounded-xl bg-green-50 text-green-700 border border-green-200 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-6 items-start">
        {{-- FORM --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-5">
                {{ $editingId ? "Modifier l'adresse" : 'Ajouter une adresse' }}
            </h2>

            @php
                $field = 'w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition';
                $label = 'text-xs font-medium text-gray-500 mb-1 block';
            @endphp

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">Prénom</label>
                    <input wire:model.defer="first_name" class="{{ $field }}">
                    @error('first_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Nom</label>
                    <input wire:model.defer="last_name" class="{{ $field }}">
                    @error('last_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <label class="{{ $label }}">Téléphone</label>
                    <input wire:model.defer="phone" class="{{ $field }}">
                    @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <label class="{{ $label }}">Adresse</label>
                    <input wire:model.defer="street_address" class="{{ $field }}">
                    @error('street_address') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Ville</label>
                    <input wire:model.defer="city" class="{{ $field }}">
                    @error('city') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Région</label>
                    <input wire:model.defer="state" class="{{ $field }}">
                    @error('state') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Code postal</label>
                    <input wire:model.defer="zip_code" class="{{ $field }}">
                    @error('zip_code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Pays</label>
                    <input wire:model.defer="country" class="{{ $field }}">
                    @error('country') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2 flex items-center gap-2 mt-1">
                    <input type="checkbox" wire:model.defer="is_default" id="is_default"
                           class="rounded border-gray-300 text-[#D4AF37] focus:ring-[#D4AF37]">
                    <label for="is_default" class="text-sm text-gray-700">Définir comme adresse par défaut</label>
                </div>
            </div>

            <div class="mt-5 flex gap-2">
                <button wire:click="save"
                        class="px-5 py-2.5 rounded-lg bg-[#D4AF37] text-white text-sm font-semibold hover:bg-[#C9A227] transition-colors">
                    Enregistrer
                </button>

                @if($editingId)
                    <button wire:click="resetForm"
                            class="px-5 py-2.5 rounded-lg border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-50 transition-colors">
                        Annuler
                    </button>
                @endif
            </div>
        </div>

        {{-- LIST --}}
        <div class="space-y-3">
            @forelse($addresses as $address)
                <div wire:key="address-{{ $address->id }}"
                     class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex gap-3 min-w-0">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-[#D4AF37]/10 flex items-center justify-center">
                                <i class="fa-solid fa-location-dot text-[#D4AF37]"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-gray-800 flex items-center gap-2 flex-wrap">
                                    {{ $address->full_name }}
                                    @if($address->is_default)
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-[#F5E6B3] text-[#8a6a00] border border-[#D4AF37]/40">
                                            Par défaut
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500 mt-1">
                                    {{ $address->formatted_address }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $address->phone }}
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-1.5 flex-shrink-0">
                            <button wire:click="edit({{ $address->id }})"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-[#D4AF37] hover:bg-[#D4AF37]/10 transition-colors">
                                <i class="fa-solid fa-pen text-sm"></i>
                            </button>
                            <button wire:click="delete({{ $address->id }})"
                                    onclick="return confirm('Supprimer cette adresse ?')"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                                <i class="fa-solid fa-trash text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 bg-white rounded-2xl border border-gray-100 shadow-sm">
                    <div class="inline-flex w-16 h-16 items-center justify-center rounded-full bg-gray-100 mb-4">
                        <i class="fa-solid fa-location-dot text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-600 font-medium">Aucune adresse enregistrée</p>
                    <p class="text-gray-400 text-sm mt-1">Ajoutez-en une depuis le formulaire.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
