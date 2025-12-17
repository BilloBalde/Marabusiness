<div class="flex flex-col h-full overflow-hidden rounded-2xl shadow-xl bg-gradient-to-br from-gray-50 to-white">
    {{-- Chat Header --}}
    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
        @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.chat'])
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
    <div class="flex-1 p-6 overflow-y-auto bg-gradient-to-b from-white via-blue-50/30 to-white">
        {{-- Date Separator --}}
        <div class="flex justify-center my-6">
            <span class="px-4 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded-full">Today</span>
        </div>

        <div class="space-y-4">
            @foreach($messages as $message)
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
                                    ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-br-none'
                                    : 'bg-white text-gray-800 border border-gray-100 rounded-bl-none shadow-xs' }}">
                                <p class="text-sm leading-relaxed">{{ $message->content }}</p>
                                
                                {{-- Message Time --}}
                                <div class="flex items-center justify-end mt-1 {{ $isSender ? 'text-blue-100' : 'text-gray-400' }}">
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
                                    : 'bg-white border-l border-t border-gray-100 left-[-4px] top-3' }}">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Input Area --}}
    <div class="p-4 border-t border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
        <div class="flex items-center gap-3">
            {{-- Attachment Button --}}
            <button class="p-2 text-gray-500 transition-colors rounded-full hover:bg-gray-100 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
            </button>
            
            {{-- Emoji Button --}}
            <button class="p-2 text-gray-500 transition-colors rounded-full hover:bg-gray-100 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </button>
            
            {{-- Message Input --}}
            <div class="flex-1 relative">
                <input
                    type="text"
                    wire:model="message"
                    wire:keydown.enter="sendMessage"
                    class="w-full px-4 py-3 pl-10 text-sm bg-white border border-gray-300 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                class="flex items-center justify-center w-10 h-10 text-white transition-all duration-200 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full hover:from-blue-600 hover:to-indigo-700 hover:shadow-lg active:scale-95"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
        
        {{-- Typing Indicator (Optional) --}}
        @if($isTyping ?? false)
            <div class="mt-2 ml-3">
                <div class="flex items-center space-x-1">
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-pulse"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-pulse delay-150"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-pulse delay-300"></div>
                    <span class="text-xs text-gray-500">typing...</span>
                </div>
            </div>
        @endif
    </div>
</div>