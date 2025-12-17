<div class="w-full max-w-[90rem] mx-auto py-14 px-4 sm:px-6 lg:px-8">

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-lg p-10 max-w-3xl mx-auto text-center">
        @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.cancel'])

        {{-- ICON --}}
        <div class="flex justify-center mb-6">
            <div class="w-24 h-24 flex items-center justify-center rounded-full bg-red-50 border border-red-200">
                <svg class="w-14 h-14 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 9v4m0 4h.01M21 12A9 9 0 1 1 3 12a9 9 0 0 1 18 0z" />
                </svg>
            </div>
        </div>

        {{-- TITLE --}}
        <h1 class="text-3xl font-bold text-red-600 mb-3">
            Paiement Échoué
        </h1>

        {{-- MESSAGE --}}
        <p class="text-gray-600 dark:text-gray-300 text-lg mb-6">
            Votre paiement n’a pas pu être validé.  
            Votre commande a été annulée pour le moment.
        </p>

        {{-- INFO CARD --}}
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl p-6 mb-8">
            <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
                Cela peut être dû à :  
                <br>• Une carte refusée  
                <br>• Une erreur réseau  
                <br>• Une annulation manuelle  
                <br>• Un solde insuffisant
            </p>
        </div>

        {{-- BUTTONS --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">

            {{-- GO BACK --}}
            <a href="/products"
                class="w-full sm:w-auto px-6 py-3 rounded-lg font-semibold border border-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:border-gray-600 text-gray-700 dark:text-gray-200 transition">
                Retour à la boutique
            </a>

            {{-- RETRY CHECKOUT --}}
            <a href="/checkout"
                class="w-full sm:w-auto px-6 py-3 rounded-lg font-semibold bg-[#D4AF37] hover:bg-[#C9A227] text-white shadow transition">
                Réessayer le paiement
            </a>
        </div>

    </div>

</div>
