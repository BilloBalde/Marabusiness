{{-- resources/views/livewire/rfq-chat.blade.php --}}
<div class="min-h-screen bg-gray-50" wire:init="loadMessages">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white border-b sticky top-0 z-10">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('user.rfqs') }}" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">RFQ #{{ $rfq->id }} Chat</h1>
                            <p class="text-sm text-gray-500">
                                Product: {{ $rfq->product->name ?? 'N/A' }} • 
                                {{ $otherParty['type'] === 'vendor' ? 'Vendor' : 'Buyer' }}: {{ $otherParty['name'] }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900">
                            Status: 
                            @php
                                $statusColors = [
                                    'pending' => 'text-yellow-600 bg-yellow-100',
                                    'quoted' => 'text-green-600 bg-green-100',
                                    'accepted' => 'text-blue-600 bg-blue-100',
                                    'rejected' => 'text-red-600 bg-red-100',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs {{ $statusColors[$rfq->status] ?? 'text-gray-600 bg-gray-100' }}">
                                {{ ucfirst($rfq->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Quantity: {{ number_format($rfq->quantity) }} units
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chat Messages -->
        <div class="p-6 space-y-6" id="chat-messages">
            @foreach($messages as $message)
                <div class="flex {{ $message['is_from_user'] && !$user->vendor ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-xs lg:max-w-md">
                        <div class="text-xs text-gray-500 mb-1 {{ $message['is_from_user'] && !$user->vendor ? 'text-right' : 'text-left' }}">
                            {{ $message['sender_name'] }} • {{ $message['created_at'] }}
                        </div>
                        <div class="p-3 rounded-lg {{ $message['is_from_user'] && !$user->vendor ? 'bg-blue-100 text-blue-900' : 'bg-gray-100 text-gray-900' }}">
                            <p class="whitespace-pre-wrap">{{ $message['message'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
            
            @if(empty($messages))
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-comment text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Start the conversation</h3>
                    <p class="text-gray-500">Send your first message to {{ $otherParty['name'] }}</p>
                </div>
            @endif
        </div>
        
        <!-- Message Input -->
        <div class="bg-white border-t sticky bottom-0">
            <div class="p-6">
                <form wire:submit.prevent="sendMessage" class="flex space-x-4">
                    <textarea
                        wire:model="newMessage"
                        rows="1"
                        placeholder="Type your message here..."
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                        x-data="{
                            resize() {
                                this.style.height = 'auto';
                                this.style.height = (this.scrollHeight) + 'px';
                            }
                        }"
                        x-init="resize()"
                        @input="resize()"
                    ></textarea>
                    <button 
                        type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium flex items-center"
                        wire:loading.attr="disabled"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>
                        <span wire:loading.remove>Send</span>
                        <span wire:loading>
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Sending...
                        </span>
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-2">
                    Press Enter to send, Shift+Enter for new line
                </p>
            </div>
        </div>
    </div>
    
    <!-- Scroll to bottom script -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Scroll to bottom on load
            scrollToBottom();
            
            // Listen for scroll event
            Livewire.on('scroll-to-bottom', () => {
                setTimeout(scrollToBottom, 100);
            });
            
            function scrollToBottom() {
                const chatMessages = document.getElementById('chat-messages');
                if (chatMessages) {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            }
            
            // Auto-resize textarea
            const textarea = document.querySelector('textarea');
            if (textarea) {
                textarea.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.form.dispatchEvent(new Event('submit', { cancelable: true }));
                    }
                });
            }
        });
    </script>
</div>