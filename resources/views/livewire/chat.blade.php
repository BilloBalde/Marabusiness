<div class="flex flex-col h-full p-4 rounded shadow-md bg-gray-50">
    {{-- Messages --}}
    <div class="flex-1 pr-2 mb-4 space-y-3 overflow-y-auto">
        @foreach($messages as $message)
            @php
                $isSender = $message->sender_id === auth()->id();
            @endphp
            <div class="flex {{ $isSender ? 'justify-end' : 'justify-start' }}">
                <div class="relative px-4 py-2 rounded-lg max-w-[75%] shadow
                    {{ $isSender
                        ? 'bg-blue-600 text-black rounded-br-none'
                        : 'bg-gray-200 text-gray-900 rounded-bl-none' }}">
                    {{ $message->content }}
                    <div class="absolute w-3 h-3 transform rotate-45
                        {{ $isSender
                            ? 'bg-blue-600 right-[-6px] bottom-2'
                            : 'bg-gray-200 left-[-6px] bottom-2' }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Input --}}
    <div class="flex items-center gap-2 pt-3 border-t">
        <input
            type="text"
            wire:model="message"
            wire:keydown.enter="sendMessage"
            class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Type your message..."
        />
        <button
            wire:click="sendMessage"
            class="px-5 py-2 text-black transition bg-green-900 rounded-lg hover:bg-blue-700"
        >
            Send
        </button>
    </div>
</div>
