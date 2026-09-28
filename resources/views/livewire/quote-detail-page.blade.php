<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-3xl mx-auto px-6">
        @include('livewire.partials.nav-header', ['tileContent' => 'sourcing.my_quotes', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">{{ $quote->sourceProduct?->name_text ?? '—' }}</h1>
                    <p class="text-sm text-gray-500">{{ $quote->sourceProduct?->sourcing?->title_text }}</p>
                </div>
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-700',
                        'processing' => 'bg-blue-100 text-blue-700',
                        'validated' => 'bg-green-100 text-green-700',
                        'cancelled' => 'bg-gray-200 text-gray-600',
                    ];
                @endphp
                <span class="text-xs font-medium px-3 py-1 rounded-full {{ $statusColors[$quote->status] ?? '' }}">
                    {{ __('sourcing.status_' . $quote->status) }}
                </span>
            </div>
            @if ($quote->moq)
                <p class="text-sm text-gray-600 mt-3">📦 MOQ {{ $quote->moq }}</p>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ __('sourcing.conversation') }}</h2>

            <div class="space-y-4 mb-6 max-h-96 overflow-y-auto">
                @foreach ($quote->messages as $message)
                    @php $isMine = $message->sender_id === auth()->id(); @endphp
                    <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-md rounded-lg px-4 py-2 {{ $isMine ? 'bg-[#D4AF37] text-white' : 'bg-gray-100 text-gray-800' }}">
                            <p class="whitespace-pre-line text-sm">{{ $message->content }}</p>
                            @foreach ($message->attachments as $attachment)
                                <a href="{{ url('uploads/' . $attachment->path) }}" target="_blank"
                                   class="block text-xs mt-1 underline {{ $isMine ? 'text-white' : 'text-[#D4AF37]' }}">
                                    📎 {{ $attachment->original_name ?? 'Fichier' }}
                                </a>
                            @endforeach
                            <p class="text-[10px] mt-1 opacity-70">{{ $message->created_at->format('d/m H:i') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <form wire:submit="sendReply" class="space-y-3">
                <textarea wire:model="reply" rows="3" placeholder="{{ __('sourcing.reply') }}"
                          class="w-full rounded-lg border-gray-300 focus:border-[#D4AF37] focus:ring-[#D4AF37]"></textarea>
                @error('reply') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                <input type="file" wire:model="attachments" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" class="text-sm text-gray-600">
                <div wire:loading wire:target="attachments" class="text-xs text-gray-400">{{ __('sourcing.uploading') }}</div>

                <button type="submit" wire:loading.attr="disabled" wire:target="sendReply"
                        class="px-5 py-2 rounded-lg bg-[#D4AF37] text-white font-medium hover:bg-[#c9a12f] disabled:opacity-60 transition">
                    {{ __('sourcing.send_reply') }}
                </button>
            </form>
        </div>
    </div>
</div>
