<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">My Addresses</h1>

        <button wire:click="resetForm"
                class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-800">
            + New Address
        </button>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-6">
        {{-- FORM --}}
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <h2 class="font-semibold text-gray-800 mb-4">
                {{ $editingId ? 'Edit Address' : 'Add Address' }}
            </h2>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-600">First name</label>
                    <input wire:model.defer="first_name" class="w-full border rounded-lg px-3 py-2">
                    @error('first_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-xs text-gray-600">Last name</label>
                    <input wire:model.defer="last_name" class="w-full border rounded-lg px-3 py-2">
                    @error('last_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <label class="text-xs text-gray-600">Phone</label>
                    <input wire:model.defer="phone" class="w-full border rounded-lg px-3 py-2">
                    @error('phone') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2">
                    <label class="text-xs text-gray-600">Street address</label>
                    <input wire:model.defer="street_address" class="w-full border rounded-lg px-3 py-2">
                    @error('street_address') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-xs text-gray-600">City</label>
                    <input wire:model.defer="city" class="w-full border rounded-lg px-3 py-2">
                    @error('city') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-xs text-gray-600">State</label>
                    <input wire:model.defer="state" class="w-full border rounded-lg px-3 py-2">
                    @error('state') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-xs text-gray-600">Zip</label>
                    <input wire:model.defer="zip_code" class="w-full border rounded-lg px-3 py-2">
                    @error('zip_code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-xs text-gray-600">Country</label>
                    <input wire:model.defer="country" class="w-full border rounded-lg px-3 py-2">
                    @error('country') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2 flex items-center gap-2 mt-2">
                    <input type="checkbox" wire:model.defer="is_default" class="rounded">
                    <span class="text-sm text-gray-700">Set as default</span>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button wire:click="save"
                        class="px-4 py-2 rounded-lg bg-[#D4AF37] text-white hover:bg-[#C9A227]">
                    Save
                </button>

                @if($editingId)
                    <button wire:click="resetForm"
                            class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200">
                        Cancel
                    </button>
                @endif
            </div>
        </div>

        {{-- LIST --}}
        <div class="space-y-3">
            @forelse($addresses as $address)
                <div class="bg-white rounded-xl shadow-sm border p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center gap-2">
                                {{ $address->full_name }}
                                @if($address->is_default)
                                    <span class="text-xs px-2 py-1 rounded-full bg-[#F5E6B3] text-[#8a6a00] border border-[#D4AF37]">
                                        Default
                                    </span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ $address->formatted_address }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ $address->phone }}
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button wire:click="edit({{ $address->id }})"
                                    class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm">
                                Edit
                            </button>
                            <button wire:click="delete({{ $address->id }})"
                                    onclick="return confirm('Delete this address?')"
                                    class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-sm">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border p-6 text-gray-600">
                    No addresses yet. Add one on the left.
                </div>
            @endforelse
        </div>
    </div>
</div>
