@push('styles')
<style>
    .chat-panel {
        background: var(--pos-surface);
        border: 1px solid var(--pos-border);
        box-shadow: var(--pos-shadow);
        color: var(--pos-text);
    }
    .chat-panel-header {
        background: linear-gradient(90deg, rgba(79,70,229,0.08), rgba(14,165,233,0.08));
        border-bottom: 1px solid var(--pos-border);
    }
    .chat-panel-body {
        background: var(--pos-surface-2);
    }
    .chat-bubble-send {
        background: linear-gradient(120deg, #4f46e5, #2563eb);
        color: #ffffff;
    }
    .chat-bubble-recv {
        background: var(--pos-surface);
        color: var(--pos-text);
        border: 1px solid var(--pos-border);
    }
    .chat-bubble-arrow {
        background: var(--pos-surface);
        border-left: 1px solid var(--pos-border);
        border-top: 1px solid var(--pos-border);
    }
    .chat-footer {
        background: linear-gradient(90deg, rgba(79,70,229,0.08), rgba(14,165,233,0.08));
        border-top: 1px solid var(--pos-border);
    }
    .chat-bubble-send-meta {
        color: rgba(255,255,255,0.8);
    }
    .chat-input-field {
        background: var(--pos-surface);
        border: 1px solid var(--pos-border);
        color: var(--pos-text);
    }
    .chat-input-field:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(79,70,229,0.2);
        border-color: var(--pos-border);
    }
    .chat-send-btn {
        background: linear-gradient(120deg, #4f46e5, #2563eb);
        color: #ffffff;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35);
        transition: transform 0.15s ease, box-shadow 0.2s ease;
    }
    .chat-send-btn:hover {
        box-shadow: 0 12px 30px rgba(37, 99, 235, 0.4);
    }
    .chat-send-btn:active {
        transform: translateY(1px);
    }
    .chat-date-pill {
        background: var(--pos-surface);
        color: var(--pos-text-subtle);
        border: 1px solid var(--pos-border);
    }
</style>
@endpush

<div class="flex flex-col h-full overflow-hidden rounded-2xl shadow-xl chat-panel">
    {{-- Chat Header --}}
    <div class="px-6 py-4 chat-panel-header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 005 10a6 6 0 0112 0c0 .459-.031.907-.086 1.333A5 5 0 0010 11z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 border-2 border-white rounded-full"></span>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">Chat Conversation</h3>
                    <p class="text-sm text-gray-500">Online • Last seen recently</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button class="p-2 text-gray-500 rounded-full hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
                <button class="p-2 text-gray-500 rounded-full hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Messages Container --}}
    <div class="flex-1 p-6 overflow-y-auto chat-panel-body">
        {{-- Date Separator --}}
        <div class="flex justify-center my-6">
            <span class="px-4 py-1 text-xs font-medium chat-date-pill rounded-full">Today</span>
        </div>

        <div class="space-y-4">
            @foreach($chatMessages as $message) {{-- Changed from $messages to $chatMessages --}}
                @php
                    $isSender = $message->sender_id === auth()->id();
                @endphp
            
                <div class="flex {{ $isSender ? 'justify-end' : 'justify-start' }}">
                    <div class="flex max-w-[85%] {{ $isSender ? 'flex-row-reverse' : '' }}">
                        {{-- Avatar --}}
                        <div class="flex-shrink-0 {{ $isSender ? 'ml-3' : 'mr-3' }}">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-r {{ $isSender ? 'from-blue-500 to-indigo-600' : 'from-gray-300 to-gray-400' }} flex items-center justify-center">
                                <span class="text-xs font-medium text-white">
                                    {{ substr($message->sender->name ?? 'U', 0, 1) }}
                                </span>
                            </div>
                        </div>
                        
                        {{-- Message Bubble --}}
                        <div class="relative">
                            <div class="px-4 py-3 rounded-2xl shadow-sm
                                {{ $isSender
                                    ? 'chat-bubble-send rounded-br-none'
                                    : 'chat-bubble-recv rounded-bl-none shadow-xs' }}">
                                <p class="text-sm leading-relaxed">{{ $message->content }}</p>
                                
                                {{-- Message Time --}}
                                <div class="flex items-center justify-end mt-1 {{ $isSender ? 'chat-bubble-send-meta' : 'text-gray-400' }}">
                                    <span class="text-xs">{{ $message->created_at->format('h:i A') }}</span>
                                    @if($isSender)
                                        <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Speech Bubble Arrow --}}
                            <div class="absolute w-2 h-2 transform rotate-45
                                {{ $isSender
                                    ? 'bg-gradient-to-r from-blue-500 to-indigo-600 right-[-4px] top-3'
                                    : 'chat-bubble-arrow left-[-4px] top-3' }}">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Input Area --}}
    <div class="p-4 chat-footer">
        <div class="flex items-center gap-3">
            {{-- Attachment Button --}}
            
            {{-- Message Input --}}
            <div class="flex-1 relative">
                <input
                    type="text"
                    wire:model="message"
                    wire:keydown.enter="sendMessage"
                    class="w-full px-4 py-3 pl-10 text-sm chat-input-field rounded-full shadow-sm"
                    placeholder="Type your message..."
                />
                <div class="absolute left-3 top-3">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
            </div>
            
            {{-- Send Button --}}
            <button
                wire:click="sendMessage"
                wire:loading.attr="disabled"
                class="flex items-center justify-center w-10 h-10 rounded-full chat-send-btn"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
    </div>
</div>