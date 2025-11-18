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
                    wire:click="selectCustomer({{ $customer->id }})"
                    class="block w-full text-left px-4 py-2 rounded hover:bg-blue-100 {{ $customerId == $customer->id ? 'bg-blue-200' : 'bg-white' }}"
                >
                    {{ $customer->name }}
                </button>
            </li>
        @empty
            <li class="text-sm text-gray-500">No customers found.</li>
        @endforelse
    </ul>
</div>
