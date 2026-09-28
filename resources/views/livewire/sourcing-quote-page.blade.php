<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-2xl mx-auto px-6">
        @include('livewire.partials.nav-header', ['tileContent' => 'sourcing.request_quote', 'hasSub' => true, 'subContent' => 'ui.navbar.sourcing', 'subLink' => 'sourcing'])

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">{{ __('sourcing.request_quote') }}</h1>
            <p class="text-gray-500 mb-6">{{ $sourceProduct->name_text }}</p>

            @if (session('success'))
                <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ session('success') }}</div>
            @endif

            <form wire:submit="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('sourcing.moq') }}</label>
                    <input type="number" wire:model="moq" min="1"
                           class="w-full rounded-lg border-gray-300 focus:border-[#D4AF37] focus:ring-[#D4AF37]">
                    @error('moq') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('sourcing.message') }}</label>
                    <textarea wire:model="message" rows="5" placeholder="{{ __('sourcing.message_placeholder') }}"
                              class="w-full rounded-lg border-gray-300 focus:border-[#D4AF37] focus:ring-[#D4AF37]"></textarea>
                    @error('message') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('sourcing.attachments') }}</label>
                    <input type="file" wire:model="attachments" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                           class="w-full text-sm text-gray-600">
                    <div wire:loading wire:target="attachments" class="text-xs text-gray-400 mt-1">{{ __('sourcing.uploading') }}</div>
                    @error('attachments.*') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                        class="w-full px-4 py-3 rounded-lg bg-[#D4AF37] text-white font-semibold hover:bg-[#c9a12f] disabled:opacity-60 transition">
                    {{ __('sourcing.send_request') }}
                </button>
            </form>
        </div>
    </div>
</div>
