<x-filament::page>
    <x-filament::form wire:submit="submitQuote">
        {{ $this->form }}
        
        <x-filament::section class="mt-6">
            <x-filament::button type="submit" color="success" icon="heroicon-o-check">
                Submit Quote
            </x-filament::button>
            
            <x-filament::button 
                href="{{ BulkRfqResource::getUrl('view', ['record' => $record]) }}" 
                color="gray" 
                tag="a"
                class="ml-2">
                Cancel
            </x-filament::button>
        </x-filament::section>
    </x-filament::form>
</x-filament::page>