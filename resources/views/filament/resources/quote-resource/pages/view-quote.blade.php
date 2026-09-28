<x-filament-panels::page>
    {{ $this->infolist }}

    <x-filament::section>
        <x-slot name="heading">
            Conversation
        </x-slot>

        @php
            $statusColors = [
                'pending' => 'bg-yellow-100 text-yellow-700',
                'processing' => 'bg-blue-100 text-blue-700',
                'validated' => 'bg-green-100 text-green-700',
                'cancelled' => 'bg-gray-200 text-gray-600',
            ];
        @endphp

        <div class="space-y-4 mb-6 max-h-96 overflow-y-auto">
            @forelse ($record->messages as $message)
                @php $isMine = $message->sender_id === auth()->id(); @endphp
                <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-md rounded-lg px-4 py-2 {{ $isMine ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 dark:text-gray-100 text-gray-800' }}">
                        <p class="whitespace-pre-line text-sm">{{ $message->content }}</p>
                        @foreach ($message->attachments as $attachment)
                            <a href="{{ url('uploads/' . $attachment->path) }}" target="_blank"
                               class="block text-xs mt-1 underline {{ $isMine ? 'text-white' : 'text-primary-600' }}">
                                📎 {{ $attachment->original_name ?? 'Fichier' }}
                            </a>
                        @endforeach
                        <p class="text-[10px] mt-1 opacity-70">{{ $message->created_at->format('d/m H:i') }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">Aucun message pour le moment.</p>
            @endforelse
        </div>

        <form wire:submit="sendReply" class="space-y-3">
            <textarea wire:model="reply" rows="3" placeholder="Votre réponse..."
                      class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-primary-500 focus:ring-primary-500 text-sm"></textarea>
            @error('reply') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            <input type="file" wire:model="attachments" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" class="text-sm text-gray-600 dark:text-gray-300">
            @error('attachments.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <div wire:loading wire:target="attachments" class="text-xs text-gray-400">Envoi en cours...</div>

            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="sendReply">
                Envoyer la réponse
            </x-filament::button>
        </form>
    </x-filament::section>
</x-filament-panels::page>
