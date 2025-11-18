<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <h2 class="text-2xl font-semibold text-gray-800">Messagerie</h2>

    {{-- Messages Box --}}
    <div class="flex-1 pr-2 space-y-2 overflow-y-auto max-h-96">
        @forelse($messages as $message)
            @php $isSender = $message->sender_id === auth()->id(); @endphp
            <div class="flex {{ $isSender ? 'justify-end' : 'justify-start' }}">
                <div class="relative px-4 py-2 max-w-[70%] rounded-lg shadow
                            {{ $isSender ? 'bg-blue-600 text-white rounded-br-none' : 'bg-gray-200 text-gray-900 rounded-bl-none' }}">
                    {{ $message->content }}
                    <div class="absolute w-3 h-3 transform rotate-45 bottom-2
                        {{ $isSender ? 'bg-blue-600 right-[-6px]' : 'bg-gray-200 left-[-6px]' }}">
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center text-gray-500">No messages yet.</p>
        @endforelse
    </div>

    {{-- Input Area --}}
    <form wire:submit.prevent="sendMessage" class="flex gap-2 pt-2 border-t">
        <input
            type="text"
            wire:model="message"
            class="flex-1 px-4 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Type your message..."
        />
        <button
            type="submit"
            class="px-5 py-2 text-white transition bg-blue-600 rounded-full hover:bg-blue-700"
        >
            Send
        </button>
    </form>
</div>
