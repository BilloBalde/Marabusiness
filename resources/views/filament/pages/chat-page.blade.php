<x-filament-panels::page>
    <div class="flex h-[80vh] border rounded shadow overflow-hidden">

        {{-- Left: Customer List for Managers --}}
        @if(auth()->user()->hasRole('manager'))
            <div class="w-1/3 p-4 overflow-y-auto bg-gray-100 border-r">
                <input
                    type="text"
                    wire:model.debounce.300ms="searchTerm"
                    placeholder="Search customers..."
                    class="w-full px-3 py-2 mb-4 border rounded"
                />

                <ul class="space-y-2">
                    @forelse($customers as $customer)
                        <li>
                            <button
                                wire:click="$set('chatWithId', {{ $customer->id }})"
                                class="block w-full text-left px-4 py-2 rounded hover:bg-blue-100 {{ $chatWithId == $customer->id ? 'bg-blue-200' : 'bg-white' }}"
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
                <livewire:chat :chatWithId="$chatWithId" />
            @else
                <div class="flex items-center justify-center h-full text-lg text-gray-400">
                    Select a customer to start chatting
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
