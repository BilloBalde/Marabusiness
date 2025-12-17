<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto bg-gray-50">
  <div class="flex items-center justify-center h-full">
    <main class="w-full max-w-md p-6 mx-auto">

      <div class="bg-white border border-gray-200 shadow-md mt-7 rounded-2xl">
        <div class="p-6 sm:p-8">

          {{-- HEADER --}}
          <div class="text-center">
            <h1 class="block text-3xl font-extrabold text-gray-900">Mot de passe oublié ?</h1>

            <p class="mt-2 text-sm text-gray-600">
              Vous vous souvenez de votre mot de passe ?
              <a href="/login"
                 class="font-semibold text-[#D4AF37] hover:underline">
                Connectez-vous ici
              </a>
            </p>
          </div>

          {{-- SUCCESS MESSAGE --}}
          @if (session('success'))
            <div class="px-4 py-3 mt-6 text-sm text-green-900 bg-green-100 border border-green-300 rounded-lg">
                ✅ {{ session('success') }}
            </div>
          @endif

          {{-- FORM --}}
          <div class="mt-8">
            <form wire:submit.prevent="save" class="space-y-6">

              {{-- EMAIL FIELD --}}
              <div>
                <label for="email" class="block mb-2 text-sm font-medium text-gray-700">Adresse email</label>

                <div class="relative">
                  <input type="email" id="email" wire:model="email"
                         class="block w-full px-4 py-3 text-sm border border-gray-300 rounded-lg
                                focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37] outline-none"
                         placeholder="exemple@domaine.com">

                  @error('email')
                    <div class="absolute inset-y-0 end-0 flex items-center pe-3 pointer-events-none">
                      <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                    </div>
                  @enderror
                </div>

                @error('email')
                  <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
              </div>

              {{-- SUBMIT BUTTON --}}
              <button type="submit"
                      class="w-full py-3 text-sm font-semibold text-white rounded-lg
                             bg-[#D4AF37] hover:bg-[#c9a227] transition">
                🔒 Réinitialiser le mot de passe
              </button>

            </form>
          </div>

        </div>
      </div>

    </main>
  </div>
</div>
