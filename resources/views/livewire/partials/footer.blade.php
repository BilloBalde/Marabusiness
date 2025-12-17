<footer class="w-full shrink-0 bg-gray-900">
  <div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 lg:pt-20 mx-auto">

    <!-- Grid -->
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-4 md:grid-cols-2">

      <!-- Brand -->
      <div>
        <a class="flex-none text-xl font-semibold text-white" href="/" aria-label="Brand">
          <img src="{{ asset('assets/images/logo.png') }}" 
               alt="MARA-BUSINESS Logo" style="width: 140px; height: 110px">
        </a>
        <p class="max-w-xl mx-auto mb-8 text-gray-200 opacity-80">
            {{ __('ui.footer.tagline') }}
        </p>
      </div>

      <!-- Company -->
      <div>
        <h4 class="font-semibold text-gray-100">{{ __('ui.footer.company') }}</h4>
        <div class="grid mt-3 space-y-3">
          <p><a class="inline-flex text-gray-200 gap-x-2 hover:text-white" href="/about-us">{{ __('ui.footer.about') }}</a></p>
          <p><a class="inline-flex text-gray-200 gap-x-2 hover:text-white" href="/privacy-policy">{{ __('ui.footer.privacy') }}</a></p>
          <p><a class="inline-flex text-gray-200 gap-x-2 hover:text-white" href="/terms-of-use">{{ __('ui.footer.terms') }}</a></p>
          <p><a class="inline-flex text-gray-200 gap-x-2 hover:text-white" href="/contact">{{ __('ui.footer.contact') }}</a></p>
        </div>
      </div>

      <!-- Download App -->
      <div>
        <h4 class="font-semibold text-gray-100">{{ __('ui.footer.download_app') }}</h4>
        <div class="flex flex-col mt-3 space-y-3">
          <a href="#" class="inline-flex items-center px-4 py-2 bg-white rounded-lg hover:bg-gray-100">
            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/78/Google_Play_Store_badge_EN.svg/512px-Google_Play_Store_badge_EN.svg.png" 
                 alt="Google Play" class="h-12">
          </a>
          <a href="#" class="inline-flex items-center px-4 py-2 bg-white rounded-lg hover:bg-gray-100">
            <img src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg" 
                 alt="App Store" class="h-12">
          </a>
        </div>
      </div>

      <!-- Subscribe -->
      <div>
        <h4 class="font-semibold text-gray-100">{{ __('ui.footer.subscribe_title') }}</h4>
        <p class="max-w-xl mb-4 text-gray-200 opacity-80">
          {{ __('ui.footer.subscribe_copy') }}
        </p>

        <form class="flex flex-col max-w-md gap-4 sm:flex-row" @submit.prevent="alert('Subscribed!')">
          <input
            type="email"
            placeholder="{{ __('ui.footer.subscribe_placeholder') }}"
            required
            class="flex-1 px-4 py-3 text-gray-900 rounded-lg"
          />
          <button type="submit" 
                  class="px-6 py-3 font-semibold text-gray-900 transition bg-white rounded-lg hover:bg-gray-200">
            {{ __('ui.footer.subscribe_button') }}
          </button>
        </form>
      </div>

    </div>
    <!-- End Grid -->

    <!-- Footer Bottom -->
    <div class="grid mt-10 sm:flex sm:justify-between sm:items-center">

      <p class="text-sm text-gray-200">
        {{ __('ui.footer.copyright', ['year' => now()->year, 'app_name' => config('app.name')]) }}
      </p>

      <!-- Social Brands -->
      <div class="flex space-x-3 mt-4 sm:mt-0">

        {{-- Facebook --}}
        <a href="https://facebook.com" target="_blank" class="hover:text-gray-200">
                <i class="fa-brands fa-facebook-f text-lg"></i>
            </a>

            <a href="https://instagram.com" target="_blank" class="hover:text-gray-200">
                <i class="fa-brands fa-instagram text-lg"></i>
            </a>

            <a href="https://wa.me/0000000000" target="_blank" class="hover:text-gray-200">
                <i class="fa-brands fa-whatsapp text-lg"></i>
            </a>

            <a href="https://linkedin.com" target="_blank" class="hover:text-gray-200">
                <i class="fa-brands fa-linkedin-in text-lg"></i>
            </a>

            <a href="#" target="_blank" class="hover:text-gray-200">
                <i class="fa-brands fa-snapchat-ghost text-lg"></i>
            </a>

      </div>

    </div>
  </div>
</footer>
