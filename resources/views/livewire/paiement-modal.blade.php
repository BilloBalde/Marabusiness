<x-modal wire:model="showModal">
@if ($showModal && $order)

<form wire:submit.prevent="save"
      class="p-6 space-y-6">

    <!-- HEADER -->
    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
        🔐 Paiement – Commande 
        <span class="text-[#D4AF37]">#{{ $order->order_number }}</span>
    </h2>

    <!-- PAYMENT OPTIONS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <label class="payment-card {{ $payment_method === 'cod' ? 'active' : '' }}">
            <input type="radio" value="cod" wire:model.live="payment_method" class="hidden">
            <div class="flex flex-col items-center">
                <i class="fa-solid fa-hand-holding-dollar text-2xl mb-1"></i>
                <span>Cash Livraison</span>
            </div>
        </label>

        <label class="payment-card {{ $payment_method === 'om' ? 'active' : '' }}">
            <input type="radio" value="om" wire:model.live="payment_method" class="hidden">
            <div class="flex flex-col items-center">
                <img src="{{ asset('assets/images/om.jpg') }}" class="w-8 mb-1">
                <span>Orange Money</span>
            </div>
        </label>

        <label class="payment-card {{ $payment_method === 'stripe' ? 'active' : '' }}">
            <input type="radio" value="stripe" wire:model.live="payment_method" class="hidden">
            <div class="flex flex-col items-center">
                <i class="fa-brands fa-cc-stripe text-2xl mb-1"></i>
                <span>Stripe (CB)</span>
            </div>
        </label>

    </div>

    @error('payment_method')
    <p class="text-sm text-red-500">{{ $message }}</p>
    @enderror


    <!-- OFFLINE PAYMENT FIELDS -->
    <div class="transition-all duration-300 ease-out"
         style="overflow: hidden; {{ $payment_method !== 'stripe' && $payment_method !== '' ? 'max-height: 500px; opacity: 1;' : 'max-height: 0; opacity: 0;' }}">

        <div class="space-y-1 mt-4">
            <label class="font-medium text-gray-700">Montant payé</label>
            <input type="number"
                   wire:model="amount"
                   class="w-full border rounded-lg px-3 py-2 focus:ring-[#D4AF37] focus:border-[#D4AF37]">
            @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="space-y-1">
            <label class="font-medium text-gray-700">Preuve de paiement</label>

            <input type="file" wire:model="image"
                   class="w-full border rounded-lg px-3 py-2">

            @error('image') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

            @if ($image)
                <img src="{{ $image->temporaryUrl() }}"
                     class="w-32 mt-3 rounded border shadow">
            @endif
        </div>
    </div>


    <!-- STRIPE MESSAGE -->
    @if ($payment_method === 'stripe')
    <div class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded animate-fadeIn">
        <p class="text-sm text-blue-700">
            Vous serez redirigé vers Stripe pour payer :
            <strong>
                {{ Number::currency(
                    $order->total_remaining,
                    $order->currency ?? optional($order->vendor?->currency)->code ?? 'USD'
                ) }}
            </strong>.
        </p>
    </div>
    @endif


    <!-- SUBMIT BUTTON -->
    <div class="text-end">
        <button type="submit"
                class="px-6 py-2 rounded-lg text-white font-semibold shadow 
                bg-[#D4AF37] hover:bg-[#c9a12f] transition">
            Confirmer Paiement
        </button>
    </div>

</form>

@endif
</x-modal>


<!-- CARD STYLE -->
<style>
.payment-card {
    @apply border rounded-lg p-4 text-center cursor-pointer bg-white shadow-sm 
           hover:shadow-md transition hover:border-[#D4AF37] hover:text-[#D4AF37];
}
.payment-card.active {
    @apply border-[#D4AF37] bg-[#fff9e6] text-[#D4AF37] shadow-md;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn { animation: fadeIn 0.3s ease-out; }
</style>


<!-- 🔥 STRIPE REDIRECT JS -->
<script>
document.addEventListener('livewire:init', () => {
    
    Livewire.on('redirect-stripe', data => {

        // Primary redirect
        window.location.href = data.url;

        // Fallback
        setTimeout(() => {
            window.location.assign(data.url);
        }, 300);
    });

    Livewire.on('close-modal', () => {
        const modal = document.querySelector('[wire\\:model="showModal"]');
        if (modal && modal.__x) {
            modal.__x.$data.show = false;
        }
    });
});
</script>
