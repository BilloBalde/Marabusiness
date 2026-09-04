<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto bg-gray-50">
  <div class="flex items-center justify-center h-full">
    <main class="w-full max-w-md p-6 mx-auto">

      <div class="bg-white border border-gray-200 shadow-md rounded-2xl">
        <div class="p-6 sm:p-8">

          {{-- HEADER --}}
          <div class="text-center">
            <h1 class="text-3xl font-extrabold text-gray-900">Se Connecter</h1>
            <p class="mt-2 text-sm text-gray-600">
              Pas de compte ?
              <a wire:navigate 
                 href="/register"
                 class="font-semibold text-[#D4AF37] hover:underline">
                Créer un compte
              </a>
            </p>
          </div>

          <hr class="my-6 border-gray-300">

          {{-- ERROR MESSAGE --}}
          @if (session('error'))
            <div class="px-4 py-3 mb-4 text-sm text-red-700 bg-red-100 border border-red-200 rounded-lg">
                {{ session('error') }}
            </div>
          @endif

          {{-- FORM --}}
          <form wire:submit.prevent="save" class="space-y-5">

            {{-- EMAIL --}}
            <div>
              <label for="email" class="block mb-2 text-sm font-medium text-gray-700">Email</label>
              <div class="relative">
                <input type="email" id="email" wire:model="email"
                       class="block w-full px-4 py-3 text-sm border border-gray-300 rounded-lg 
                              focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37] outline-none"
                       required>
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

            {{-- PASSWORD --}}
            <div>
              <div class="flex items-center justify-between">
                <label for="password" class="block mb-2 text-sm font-medium text-gray-700">Mot de passe</label>

                <a href="/forgot-password"
                   class="text-sm font-semibold text-[#D4AF37] hover:underline">
                   Mot de Passe Oublié ?
                </a>
              </div>

              <div class="relative">
                <input type="password" id="password" wire:model="password"
                       class="block w-full px-4 py-3 text-sm border border-gray-300 rounded-lg 
                              focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37] outline-none"
                       required>
                @error('password')
                  <div class="absolute inset-y-0 end-0 flex items-center pe-3 pointer-events-none">
                    <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                  </div>
                @enderror
              </div>

              @error('password')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
              @enderror
            </div>

            {{-- REMEMBER ME --}}
            <div class="flex items-center space-x-2">
              <input type="checkbox" id="remember" wire:model="remember"
                     class="w-4 h-4 text-[#D4AF37] border-gray-300 rounded focus:ring-[#D4AF37]">

              <label for="remember" class="text-sm text-gray-700">
                Se souvenir de moi
              </label>
            </div>

            {{-- SUBMIT BUTTON --}}
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="w-full py-3 text-sm font-semibold text-white rounded-lg 
                           bg-[#D4AF37] hover:bg-[#c9a227] transition disabled:opacity-60">
              <span wire:loading.remove>Se Connecter</span>
              <span wire:loading>Connexion en cours...</span>
            </button>

            {{-- SOCIAL LOGIN --}}
            <div class="mt-6">
              <a href="{{ route('google.redirect') }}"
                class="flex items-center justify-center w-full py-3 mb-3 text-sm font-medium bg-white 
                       border border-gray-300 rounded-lg hover:bg-gray-50">
                <img src="{{ asset('assets/images/logo_google.png') }}" class="w-5 mr-3" alt="">
                Continuer avec Google
              </a>
            </div>

            <hr class="my-6 border-gray-300">

          </form>
        </div>
      </div>
    </main>
  </div>
</div>

