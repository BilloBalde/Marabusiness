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
        padding: 1rem;
        border-radius: 1rem;
    }

    .chat-shell {
        background: var(--pos-surface);
        border: 1px solid var(--pos-border);
        box-shadow: var(--pos-shadow);
        border-radius: 1rem;
        overflow: hidden;
    }

    .chat-list {
        background: var(--pos-surface-2);
        border-right: 1px solid var(--pos-border);
    }

    .chat-list button {
        background: var(--pos-surface);
        color: var(--pos-text);
        border: 1px solid transparent;
    }

    .chat-list button:hover {
        background: var(--pos-surface-2);
        border-color: var(--pos-border);
    }

    .chat-list .chat-list-active {
        background: rgba(79,70,229,0.12);
        border-color: var(--pos-border);
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
</style>
@endpush

<x-filament-panels::page>
    <div class="chat-wrapper">
        
        <div class="chat-shell flex h-[80vh] overflow-hidden">

        {{-- Left: Customer List for Managers --}}
        @if(auth()->check() && auth()->user()->hasRole('manager'))
            <div class="w-1/3 p-4 overflow-y-auto chat-list">
                <input
                    type="text"
                    wire:model.debounce.300ms="searchTerm"
                    placeholder="Search customers..."
                    class="w-full chat-input mb-4"
                />

                <ul class="space-y-2">
                    @forelse($customers as $customer)
                        <li>
                            <button
                                wire:click="$set('chatWithId', {{ $customer->id }})"
                                class="block w-full text-left px-4 py-2 rounded {{ $chatWithId == $customer->id ? 'chat-list-active' : '' }}"
                            >
                                {{ $customer->name }}
                            </button>
                        </li>
                    @empty
                        <li class="text-sm text-gray-500">No customers found.</li>
                    @endforelse
                </ul>
            </div>
        @endif

        {{-- Right: Chat Component --}}
        <div class="flex-1 p-4 bg-green-50">
            @if($chatWithId)
                <livewire:chat :chatWithId="$chatWithId" :key="'chat-'.$chatWithId" />
            @else
                <div class="flex items-center justify-center h-full text-lg text-gray-400">
                    Select a customer to start chatting
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
