<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.my_chats', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Mes discussions</h1>
        <div class="w-16 h-1 bg-[#D4AF37] mt-4 rounded-full"></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        {{-- Left Sidebar: Contacts --}}
        <div class="md:col-span-1 bg-white rounded-2xl border border-gray-100 shadow-sm p-4 h-fit">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Contacts</h2>

            {{-- Search --}}
            <div class="relative mb-4">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input
                    type="text"
                    wire:model.debounce.300ms="searchTerm"
                    class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37]"
                    placeholder="Rechercher un contact..."
                />
            </div>

            {{-- Contacts List --}}
            <div class="space-y-1.5 max-h-[520px] overflow-y-auto">
                @forelse($contacts as $contact)
                    @php
                        $contactId = $contact['id'] ?? null;
                        $contactName = $contact['name'] ?? '';
                        $contactVendor = $contact['vendor'] ?? null;
                        $contactRoles = $contact['roles'] ?? [];
                        $contactUnread = $contact['unread_messages'] ?? 0;
                        $isActiveContact = $chatWithId == $contactId;
                    @endphp

                    @if($contactId)
                        <button
                            wire:click="selectContact({{ $contactId }})"
                            wire:key="contact-{{ $contactId }}"
                            class="w-full text-left p-3 rounded-xl transition-colors duration-150 {{ $isActiveContact ? 'bg-[#D4AF37]/10 border border-[#D4AF37]/30' : 'border border-transparent hover:bg-gray-50' }}"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#D4AF37] to-[#c9a12f] flex items-center justify-center">
                                        <span class="text-white font-semibold text-sm">
                                            {{ substr($contactName, 0, 1) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-3 flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 truncate">
                                        {{ $contactName }}
                                        @if($contactVendor)
                                            <span class="text-xs text-gray-500 ml-1">
                                                ({{ $contactVendor['store_name'] ?? '' }})
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        @if(in_array('manager', $contactRoles))
                                            Support MARA BUSINESS
                                        @elseif(in_array('vendor', $contactRoles))
                                            Vendeur
                                        @elseif(in_array('customer', $contactRoles))
                                            Client
                                        @endif
                                    </div>
                                </div>
                                @if($contactUnread > 0)
                                    <span class="flex-shrink-0 ml-2 min-w-[1.5rem] h-6 px-2 rounded-full bg-[#D4AF37] text-white text-xs font-semibold flex items-center justify-center">
                                        {{ $contactUnread }}
                                    </span>
                                @endif
                            </div>
                        </button>
                    @endif
                @empty
                    <div class="text-center py-8 text-gray-400 text-sm">
                        Aucun contact trouvé
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Right Side: Chat --}}
        <div class="md:col-span-3">
            @if($chatWithId)
                @php
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
                    <div class="bg-white rounded-t-2xl border border-b-0 border-gray-100 shadow-sm p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#D4AF37] to-[#c9a12f] flex items-center justify-center">
                                    <span class="text-white font-semibold text-lg">
                                        {{ substr($currentContact['name'] ?? '', 0, 1) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-base font-semibold text-gray-900">
                                    {{ $currentContact['name'] ?? '' }}
                                    @if(isset($currentContact['vendor']))
                                        <span class="text-sm font-normal text-gray-500 ml-1">
                                            ({{ $currentContact['vendor']['store_name'] ?? '' }})
                                        </span>
                                    @endif
                                </h3>
                                <div class="text-xs text-gray-500">
                                    @if(in_array('manager', $currentContact['roles'] ?? []))
                                        Support MARA BUSINESS
                                    @elseif(in_array('vendor', $currentContact['roles'] ?? []))
                                        Vendeur · {{ $currentContact['vendor']['store_name'] ?? '' }}
                                    @elseif(in_array('customer', $currentContact['roles'] ?? []))
                                        Client
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Messages Box --}}
                    <div class="bg-gray-50 border-x border-gray-100 p-4 h-[420px] overflow-y-auto">
                        @forelse($messages as $message)
                            @php
                                $isSender = ($message['sender_id'] ?? null) === auth()->id();
                                $senderName = $message['sender']['name'] ?? '';
                                $messageContent = $message['content'] ?? '';
                                $createdAt = $message['created_at'] ?? null;
                            @endphp
                            <div class="flex {{ $isSender ? 'justify-end' : 'justify-start' }} mb-4">
                                <div class="flex max-w-[80%] {{ $isSender ? '' : 'items-end' }}">
                                    @if(!$isSender)
                                        <div class="flex-shrink-0 mr-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-white text-xs font-medium">
                                                    {{ substr($senderName, 0, 1) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="px-4 py-2.5 rounded-2xl {{ $isSender
                                        ? 'bg-gradient-to-br from-[#D4AF37] to-[#c9a12f] text-white rounded-br-md'
                                        : 'bg-white text-gray-800 shadow-sm border border-gray-100 rounded-bl-md' }}">
                                        <p class="text-sm leading-relaxed whitespace-pre-line">{{ $messageContent }}</p>
                                        @if($createdAt)
                                            <div class="text-[11px] mt-1 {{ $isSender ? 'text-white/75' : 'text-gray-400' }}">
                                                {{ \Carbon\Carbon::parse($createdAt)->format('d/m H:i') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-14 text-gray-400">
                                <i class="fa-regular fa-comments text-4xl mb-3 block"></i>
                                <p>Aucun message pour le moment. Lancez la conversation !</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Input Area --}}
                    <div class="bg-white rounded-b-2xl border border-t-0 border-gray-100 shadow-sm p-4">
                        <form wire:submit.prevent="sendMessage" class="flex gap-3">
                            <input
                                type="text"
                                wire:model="message"
                                wire:keydown.enter.prevent="sendMessage"
                                class="flex-1 px-4 py-2.5 border border-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37]"
                                placeholder="Écrivez votre message..."
                            />
                            <button
                                type="submit"
                                class="w-11 h-11 flex-shrink-0 flex items-center justify-center text-white bg-gradient-to-br from-[#D4AF37] to-[#c9a12f] rounded-full hover:shadow-md transition-shadow"
                            >
                                <i class="fa-solid fa-paper-plane text-sm"></i>
                            </button>
                        </form>
                    </div>
                @endif
            @else
                {{-- No Contact Selected --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm h-full flex flex-col items-center justify-center p-12 min-h-[400px]">
                    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <i class="fa-regular fa-comments text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-1">Choisissez un contact</h3>
                    <p class="text-gray-500 text-center text-sm">
                        Sélectionnez un contact dans la liste pour démarrer la discussion.
                    </p>
                </div>
            @endif

            {{-- Flash Messages --}}
            @if(session()->has('error'))
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-center text-red-700 text-sm">
                        <i class="fa-solid fa-circle-exclamation mr-2"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
