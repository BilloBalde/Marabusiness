<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.my_chats', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        {{-- Left Sidebar: Contacts --}}
        <div class="md:col-span-1 bg-white rounded-lg shadow p-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Contacts</h2>
            
            {{-- Search --}}
            <div class="mb-4">
                <input
                    type="text"
                    wire:model.debounce.300ms="searchTerm"
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Search contacts..."
                />
            </div>
            
            {{-- Contacts List --}}
            <div class="space-y-2 max-h-[500px] overflow-y-auto">
                @forelse($contacts as $contact)
                    @php
                        // Convert array to object if needed, or access as array
                        $contactId = $contact['id'] ?? null;
                        $contactName = $contact['name'] ?? '';
                        $contactVendor = $contact['vendor'] ?? null;
                        $contactRoles = $contact['roles'] ?? [];
                    @endphp
                    
                    @if($contactId)
                        <button
                            wire:click="selectContact({{ $contactId }})"
                            class="w-full text-left p-3 rounded-lg transition-all duration-200 {{ $chatWithId == $contactId ? 'bg-blue-50 border border-blue-200' : 'hover:bg-gray-50' }}"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                                        <span class="text-white font-medium text-sm">
                                            {{ substr($contactName, 0, 1) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-3 flex-1">
                                    <div class="font-medium text-gray-900">
                                        {{ $contactName }}
                                        @if($contactVendor)
                                            <span class="text-xs text-gray-500 ml-1">
                                                ({{ $contactVendor['store_name'] ?? '' }})
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        @if(in_array('manager', $contactRoles))
                                            Manager
                                        @elseif(in_array('vendor', $contactRoles))
                                            Vendor
                                        @elseif(in_array('customer', $contactRoles))
                                            Customer
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </button>
                    @endif
                @empty
                    <div class="text-center py-8 text-gray-500">
                        No contacts found
                    </div>
                @endforelse
            </div>
        </div>
        
        {{-- Right Side: Chat --}}
        <div class="md:col-span-3">
            @if($chatWithId)
                @php
                    // Find current contact from array
                    $currentContact = null;
                    foreach ($contacts as $contact) {
                        if (($contact['id'] ?? null) == $chatWithId) {
                            $currentContact = $contact;
                            break;
                        }
                    }
                @endphp
                
                @if($currentContact)
                    {{-- Chat Header --}}
                    <div class="bg-white rounded-t-lg shadow-sm p-4 border-b">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                                    <span class="text-white font-medium text-lg">
                                        {{ substr($currentContact['name'] ?? '', 0, 1) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ $currentContact['name'] ?? '' }}
                                    @if(isset($currentContact['vendor']))
                                        <span class="text-sm font-normal text-gray-500 ml-2">
                                            ({{ $currentContact['vendor']['store_name'] ?? '' }})
                                        </span>
                                    @endif
                                </h3>
                                <div class="text-sm text-gray-500">
                                    @if(in_array('manager', $currentContact['roles'] ?? []))
                                        Manager
                                    @elseif(in_array('vendor', $currentContact['roles'] ?? []))
                                        Vendor • {{ $currentContact['vendor']['store_name'] ?? '' }}
                                    @elseif(in_array('customer', $currentContact['roles'] ?? []))
                                        Customer
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Messages Box --}}
                    <div class="bg-gray-50 p-4 h-[400px] overflow-y-auto">
                        @forelse($messages as $message)
                            @php 
                                $isSender = ($message['sender_id'] ?? null) === auth()->id();
                                $senderName = $message['sender']['name'] ?? '';
                                $messageContent = $message['content'] ?? '';
                                $createdAt = $message['created_at'] ?? null;
                            @endphp
                            <div class="flex {{ $isSender ? 'justify-end' : 'justify-start' }} mb-4">
                                <div class="flex max-w-[80%]">
                                    {{-- Avatar for received messages --}}
                                    @if(!$isSender)
                                        <div class="flex-shrink-0 mr-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-gray-400 to-gray-500 flex items-center justify-center">
                                                <span class="text-white text-xs font-medium">
                                                    {{ substr($senderName, 0, 1) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    {{-- Message Bubble --}}
                                    <div class="relative">
                                        <div class="px-4 py-3 rounded-2xl {{ $isSender 
                                            ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-br-none' 
                                            : 'bg-white text-gray-900 shadow-sm rounded-bl-none' }}">
                                            <p class="text-sm leading-relaxed">{{ $messageContent }}</p>
                                            @if($createdAt)
                                                <div class="text-xs mt-1 {{ $isSender ? 'text-blue-100' : 'text-gray-500' }}">
                                                    {{ \Carbon\Carbon::parse($createdAt)->format('h:i A • M d') }}
                                                </div>
                                            @endif
                                        </div>
                                        
                                        {{-- Arrow --}}
                                        <div class="absolute w-2 h-2 transform rotate-45 bottom-3
                                            {{ $isSender 
                                                ? 'bg-gradient-to-r from-blue-500 to-indigo-600 right-[-4px]' 
                                                : 'bg-white left-[-4px]' }}">
                                        </div>
                                    </div>
                                    
                                    {{-- Avatar for sent messages --}}
                                    @if($isSender)
                                        <div class="flex-shrink-0 ml-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                                                <span class="text-white text-xs font-medium">
                                                    {{ substr(auth()->user()->name, 0, 1) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-10 text-gray-500">
                                <div class="mb-2">
                                    <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </div>
                                <p>No messages yet. Start a conversation!</p>
                            </div>
                        @endforelse
                    </div>
                    
                    {{-- Input Area --}}
                    <div class="bg-white rounded-b-lg shadow-sm p-4 border-t">
                        <form wire:submit.prevent="sendMessage" class="flex gap-3">
                            <input
                                type="text"
                                wire:model="message"
                                wire:keydown.enter.prevent="sendMessage"
                                class="flex-1 px-4 py-3 border rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Type your message..."
                            />
                            <button
                                type="submit"
                                class="px-6 py-3 text-white bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 font-medium shadow hover:shadow-lg"
                            >
                                Send
                            </button>
                        </form>
                    </div>
                @endif
            @else
                {{-- No Contact Selected --}}
                <div class="bg-white rounded-lg shadow h-full flex flex-col items-center justify-center p-8">
                    <div class="mb-4">
                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Select a Contact</h3>
                    <p class="text-gray-500 text-center mb-6">
                        Choose a manager or vendor from the contacts list to start chatting
                    </p>
                </div>
            @endif
            
            {{-- Flash Messages --}}
            @if(session()->has('error'))
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center text-red-700">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>