@push('styles')
<style>
    :root {
        --pos-page-bg: linear-gradient(135deg, #f3f4f6 0%, #ffffff 100%);
        --pos-surface: #ffffff;
        --pos-surface-2: #f8fafc;
        --pos-border: #e2e8f0;
        --pos-text: #0f172a;
        --pos-text-subtle: #475569;
        --pos-shadow: 0 15px 50px rgba(15, 23, 42, 0.08);
    }

    .dark {
        --pos-page-bg: linear-gradient(135deg, #0f172a 0%, #111827 100%);
        --pos-surface: #0b1220;
        --pos-surface-2: #0f172a;
        --pos-border: #1f2937;
        --pos-text: #e2e8f0;
        --pos-text-subtle: #94a3b8;
        --pos-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
    }

    .chat-wrapper {
        background: var(--pos-page-bg);
        color: var(--pos-text);
        padding: 0.5rem;
        border-radius: 1rem;
        min-height: calc(100vh - 2rem);
    }

    @media (min-width: 768px) {
        .chat-wrapper {
            padding: 1rem;
        }
    }

    .chat-shell {
        background: var(--pos-surface);
        border: 1px solid var(--pos-border);
        box-shadow: var(--pos-shadow);
        border-radius: 1rem;
        overflow: hidden;
        height: calc(100vh - 4rem);
        display: flex;
        flex-direction: column;
    }

    @media (min-width: 768px) {
        .chat-shell {
            flex-direction: row;
            height: calc(100vh - 6rem);
        }
    }

    /* Contact List - Mobile: 100% width, Desktop: 33% width */
    .chat-list-container {
        width: 100%;
        height: auto;
        max-height: 40vh;
        overflow-y: auto;
        border-bottom: 1px solid var(--pos-border);
        background: var(--pos-surface-2);
        display: block;
    }

    @media (min-width: 768px) {
        .chat-list-container {
            width: 33.333%;
            max-height: none;
            height: 100%;
            border-right: 1px solid var(--pos-border);
            border-bottom: none;
        }
    }

    /* Chat Area - Mobile: hidden when no contact selected, Desktop: always visible */
    .chat-area-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: calc(60vh - 2rem);
        overflow: hidden;
    }

    @media (min-width: 768px) {
        .chat-area-container {
            height: 100%;
            display: flex !important;
        }
    }

    /* Mobile-only: Show/Hide based on selection */
    @media (max-width: 767px) {
        .chat-shell.showing-list .chat-area-container {
            display: none;
        }
        
        .chat-shell.showing-chat .chat-list-container {
            display: none;
        }
        
        .chat-shell.showing-chat .chat-area-container {
            display: flex;
        }
    }

    .chat-list {
        padding: 0.75rem;
    }

    @media (min-width: 768px) {
        .chat-list {
            padding: 1rem;
        }
    }

    .chat-list-search {
        position: sticky;
        top: 0;
        z-index: 10;
        background: var(--pos-surface-2);
        padding: 0.75rem;
        border-bottom: 1px solid var(--pos-border);
    }

    .chat-list button {
        background: var(--pos-surface);
        color: var(--pos-text);
        border: 1px solid transparent;
        transition: all 0.2s ease;
        width: 100%;
        text-align: left;
        padding: 0.75rem;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-list button:hover {
        background: var(--pos-surface-2);
        border-color: var(--pos-border);
        transform: translateY(-1px);
    }

    .chat-list .chat-list-active {
        background: rgba(79,70,229,0.12);
        border-color: rgba(79,70,229,0.3);
        color: var(--pos-text);
    }

    .chat-input {
        background: var(--pos-surface);
        border: 1px solid var(--pos-border);
        color: var(--pos-text);
        border-radius: 0.6rem;
        padding: 0.65rem 0.75rem;
    }

    .chat-input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }

    .chat-empty {
        color: var(--pos-text-subtle);
    }
    
    .unread-badge {
        background: #ef4444;
        color: white;
        font-size: 0.75rem;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
    }
    
    .customer-details {
        background: var(--pos-surface);
        border-bottom: 1px solid var(--pos-border);
        padding: 0.75rem;
    }
    
    @media (min-width: 768px) {
        .customer-details {
            padding: 1rem;
        }
    }
    
    .stat-card {
        background: var(--pos-surface-2);
        border: 1px solid var(--pos-border);
        border-radius: 0.5rem;
        padding: 0.5rem;
    }
    
    /* Mobile back button */
    .mobile-back-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        background: var(--pos-surface-2);
        border: 1px solid var(--pos-border);
        color: var(--pos-text);
        margin-right: 0.75rem;
        flex-shrink: 0;
    }
    
    @media (min-width: 768px) {
        .mobile-back-button {
            display: none;
        }
    }
    
    /* Mobile header */
    .mobile-chat-header {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        border-bottom: 1px solid var(--pos-border);
        background: var(--pos-surface);
    }
    
    @media (min-width: 768px) {
        .mobile-chat-header {
            display: none;
        }
    }
    
    /* Better mobile scrolling */
    .chat-messages-container {
        flex: 1;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding: 0.75rem;
    }
    
    @media (min-width: 768px) {
        .chat-messages-container {
            padding: 1rem;
        }
    }
    
    /* Responsive text sizes */
    .customer-name {
        font-size: 1rem;
        font-weight: 600;
    }
    
    @media (min-width: 768px) {
        .customer-name {
            font-size: 1.125rem;
        }
    }
</style>
@endpush

<x-filament-panels::page>
    <div class="chat-wrapper">
        <div class="chat-shell {{ $chatWithId ? 'showing-chat' : 'showing-list' }}">
            {{-- Left: Contact List for All Users --}}
            @if(auth()->check())
                <div class="chat-list-container">
                    <div class="chat-list-search">
                        <input
                            type="text"
                            wire:model.debounce.300ms="searchTerm"
                            placeholder="Search contacts..."
                            class="w-full chat-input"
                        />
                    </div>
                    
                    <div class="chat-list">
                        <ul>
                            @forelse($customers as $customer)
                                <li>
                                    <button
                                        wire:click="customerSelected({{ $customer->id }})"
                                        @if($chatWithId == $customer->id)
                                            wire:click="markMessagesAsRead({{ $customer->id }})"
                                        @endif
                                        class="block w-full text-left px-3 py-3 rounded flex justify-between items-center {{ $chatWithId == $customer->id ? 'chat-list-active' : '' }}"
                                    >
                                        <div class="flex-1 truncate min-w-0">
                                            <div class="font-medium text-sm md:text-base truncate">
                                                {{ $customer->name }}
                                                @if($customer->vendor ?? false)
                                                    <span class="text-xs text-gray-500 ml-1 md:ml-2 block md:inline">
                                                        ({{ $customer->vendor->store_name }})
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 truncate mt-1">
                                                @if(auth()->user()->hasRole('vendor') && isset($customer->total_orders))
                                                    {{ $customer->total_orders }} orders • 
                                                    {{ $this->formatCurrency($customer->total_spent ?? 0, $customer->currency_symbol ?? null, $customer->currency_code ?? null) }}
                                                @elseif(isset($customer->email))
                                                    {{ Str::limit($customer->email, 20) }}
                                                @endif
                                            </div>
                                        </div>
                                        
                                        @if(($customer->unread_messages ?? 0) > 0)
                                            <span class="unread-badge ml-2 flex-shrink-0">
                                                {{ $customer->unread_messages }}
                                            </span>
                                        @endif
                                    </button>
                                </li>
                            @empty
                                <li class="text-sm text-gray-500 p-4 text-center">
                                    @if(auth()->user()->hasRole('vendor'))
                                        No customers with orders found.
                                    @elseif(auth()->user()->hasRole('manager'))
                                        No customers found.
                                    @else
                                        No contacts found.
                                    @endif
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Right: Chat Component with Customer Details --}}
            <div class="chat-area-container">
                @if($chatWithId && $selectedCustomerDetails)
                    {{-- Mobile Header with Back Button --}}
                    <div class="mobile-chat-header md:hidden">
                        <button 
                            wire:click="$set('chatWithId', null)" 
                            class="mobile-back-button"
                            aria-label="Back to contacts"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </button>
                        <div class="flex-1 min-w-0">
                            <div class="customer-name truncate">{{ $selectedCustomerDetails['name'] }}</div>
                            @if(($selectedCustomerDetails['unread_messages'] ?? 0) > 0)
                                <span class="unread-badge text-xs mt-1">
                                    {{ $selectedCustomerDetails['unread_messages'] }} unread
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Customer Details Header (Desktop) --}}
                    <div class="customer-details hidden md:block">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="customer-name">
                                    {{ $selectedCustomerDetails['name'] }}
                                </h3>
                                <div class="text-sm text-gray-500 mt-1">
                                    @if(isset($selectedCustomerDetails['store_name']))
                                        {{ $selectedCustomerDetails['store_name'] }} •
                                    @endif
                                    {{ $selectedCustomerDetails['email'] }}
                                    @if(isset($selectedCustomerDetails['phone']))
                                        • {{ $selectedCustomerDetails['phone'] }}
                                    @endif
                                </div>
                            </div>
                            
                            @if(($selectedCustomerDetails['unread_messages'] ?? 0) > 0)
                                <span class="unread-badge">
                                    {{ $selectedCustomerDetails['unread_messages'] }} unread
                                </span>
                            @endif
                        </div>
                        
                        {{-- Stats for Vendors --}}
                        @if(auth()->user()->hasRole('vendor') && isset($selectedCustomerDetails['total_orders']))
                            <div class="grid grid-cols-3 gap-2 md:gap-3 mt-3 md:mt-4">
                                <div class="stat-card text-center">
                                    <div class="font-bold text-base md:text-lg text-blue-600">
                                        {{ $selectedCustomerDetails['total_orders'] }}
                                    </div>
                                    <div class="text-xs text-gray-500">Orders</div>
                                </div>
                                <div class="stat-card text-center">
                                    <div class="font-bold text-base md:text-lg text-green-600">
                                        {{ $this->formatCurrency(
                                            $selectedCustomerDetails['total_spent'] ?? 0,
                                            $selectedCustomerDetails['currency_symbol'] ?? null,
                                            $selectedCustomerDetails['currency_code'] ?? null
                                        ) }}
                                    </div>
                                    <div class="text-xs text-gray-500">Spent</div>
                                </div>
                                <div class="stat-card text-center">
                                    <div class="font-bold text-base md:text-lg text-purple-600">
                                        {{ $this->formatDate($selectedCustomerDetails['last_order']['created_at'] ?? null) }}
                                    </div>
                                    <div class="text-xs text-gray-500">Last Order</div>
                                </div>
                            </div>
                            
                            @if($selectedCustomerDetails['last_order'] ?? false)
                                <div class="mt-3 text-sm flex flex-wrap items-center gap-2">
                                    <span class="text-gray-600 text-xs md:text-sm">Last order:</span>
                                    <span class="font-medium text-xs md:text-sm">
                                        #{{ $selectedCustomerDetails['last_order']['order_number'] }}
                                    </span>
                                    <span class="{{ $this->getOrderStatusColor($selectedCustomerDetails['last_order']['status']) }} px-2 py-1 rounded-full text-xs">
                                        {{ ucfirst($selectedCustomerDetails['last_order']['status']) }}
                                    </span>
                                    <span class="font-medium text-xs md:text-sm">
                                        {{ $this->formatCurrency(
                                            $selectedCustomerDetails['last_order']['grand_total'] ?? 0,
                                            $selectedCustomerDetails['currency_symbol'] ?? null,
                                            $selectedCustomerDetails['currency_code'] ?? null
                                        ) }}
                                    </span>
                                </div>
                            @endif
                        @endif
                    </div>
                    
                    {{-- Chat Messages Container --}}
                    <div class="chat-messages-container">
                        <livewire:chat 
                            :chatWithId="$chatWithId" 
                            :key="'chat-'.$chatWithId"
                            @message-sent="$refresh"
                        />
                    </div>
                @else
                    <div class="flex items-center justify-center h-full text-center px-4">
                        <div>
                            <div class="text-4xl mb-4 text-gray-300">💬</div>
                            <h3 class="text-lg font-medium text-gray-400 mb-2 chat-empty">
                                @if(auth()->user()->hasRole('vendor'))
                                    Select a customer to view order history and start chatting
                                @elseif(auth()->user()->hasRole('manager'))
                                    Select a customer to start chatting
                                @else
                                    Select a contact to start chatting
                                @endif
                            </h3>
                            <p class="text-sm text-gray-500">
                                Tap on a contact from the list to start conversation
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>