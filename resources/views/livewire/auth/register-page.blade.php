<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="flex items-center h-full">
        <main class="w-full max-w-md p-6 mx-auto">

            <div class="bg-white border border-gray-200 shadow-lg rounded-2xl">

                <div class="p-6 sm:p-8">

                    {{-- TITLE --}}
                    <div class="text-center">
                        <h1 class="text-2xl font-bold text-gray-800">Créer un Compte</h1>

                        <p class="mt-2 text-sm text-gray-600">
                            Déjà inscrit ?
                            <a wire:navigate
                               href="/login"
                               class="font-medium text-[#D4AF37] hover:underline">
                                Se connecter
                            </a>
                        </p>
                    </div>

                    <hr class="my-6 border-gray-300">

                    {{-- REGISTER FORM --}}
                    <form wire:submit.prevent="save" class="space-y-5">

                        {{-- FULL NAME --}}
                        <div>
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-700">
                                Nom Complet
                            </label>

                            <div class="relative">
                                <input type="text" id="name" wire:model="name"
                                       class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl
                                              focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37]
                                              dark:bg-slate-900 dark:border-gray-700 dark:text-gray-300">

                                @error('name')
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-600 text-lg">⚠</span>
                                @enderror
                            </div>

                            @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>


                        {{-- EMAIL --}}
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-700">
                                Email
                            </label>

                            <div class="relative">
                                <input type="email" id="email" wire:model="email"
                                       class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl
                                              focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37]">

                                @error('email')
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-600 text-lg">⚠</span>
                                @enderror
                            </div>

                            @error('email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>


                        {{-- PASSWORD --}}
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-700">
                                Mot de Passe
                            </label>

                            <div class="relative">
                                <input type="password" id="password" wire:model="password"
                                       class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl
                                              focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37]">

                                @error('password')
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-600 text-lg">⚠</span>
                                @enderror
                            </div>

                            @error('password')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>


                        {{-- CONFIRM PASSWORD --}}
                        <div>
                            <label for="password_confirmation" class="block mb-2 text-sm font-medium text-gray-700">
                                Confirmer Mot de Passe
                            </label>

                            <div class="relative">
                                <input type="password" id="password_confirmation" wire:model="password_confirmation"
                                       class="w-full px-4 py-3 text-sm border border-gray-300 rounded-xl
                                              focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37]">

                                @error('password_confirmation')
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-600 text-lg">⚠</span>
                                @enderror
                            </div>

                            @error('password_confirmation')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>


                        {{-- SUBMIT BUTTON --}}
                        <button type="submit"
                                class="w-full py-3 mt-3 text-sm font-semibold text-white
                                       bg-[#D4AF37] hover:bg-[#c8a031] 
                                       rounded-xl shadow-md transition">
                            S'inscrire
                        </button>

                    </form>
                </div>
            </div>

        </main>
    </div>
</div>
