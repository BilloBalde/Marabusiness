<x-modal wire:model="showModal">
    @if ($showModal && $order)
        <form wire:submit.prevent="save" class="p-4 space-y-4">
            <h2 class="text-lg font-semibold text-gray-700">
                Paiement pour la commande
                @if ($order)
                    #{{ $order->order_number }}
                @else
                    <!-- Optional fallback or loading state -->
                    ...
                @endif
            </h2>


            <div>
                <label class="block mb-1">Méthode de paiement</label>
                <select wire:model="payment_method" class="w-full border rounded px-3 py-2">
                    <option value="">Sélectionnez une méthode</option>
                    <option value="bank">Virement bancaire</option>
                    <option value="cod">Paiement à la livraison</option>
                    <option value="om">Orange Money</option>
                </select>
                @error('payment_method') <span class="text-sm text-red-500">{{ $message }}</span> @enderror

            <div>
                <label class="block mb-1">Montant payé</label>
                <input type="number" wire:model="amount" class="w-full border rounded px-3 py-2">
                @error('amount') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block mb-1">Capture d'écran du paiement</label>
                <input type="file" wire:model="image" class="w-full border rounded px-3 py-2">
                @error('image') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" class="w-32 mt-2 rounded border">
                @endif
            </div>

            <div class="text-end">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    Valider Paiement
                </button>
            </div>
        </form>
    @endif
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('close-modal', () => {
                // Example using Alpine.js
                //document.getElementById('paiement-modal')?.close?.();

                // OR manually hide the modal (depending on how you implemented it)
                $('#paiement-modal').modal('hide'); // if you're using Bootstrap
            });
        });
    </script>

</x-modal>
