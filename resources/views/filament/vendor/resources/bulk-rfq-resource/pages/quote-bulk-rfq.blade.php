{{--
    Filament 2 syntax left over from an upgrade: <x-filament::page> and
    <x-filament::form> no longer exist in Filament 3, so this page threw as soon as a
    vendor opened it — which is why no vendor has ever managed to submit a quote.
    The section and button components below keep the filament:: namespace, which is
    still correct in v3; only page and form moved to filament-panels::.
--}}
<x-filament-panels::page>
    <x-filament-panels::form wire:submit="submitQuote">
        {{ $this->form }}

        <x-filament::section class="mt-6">
            <x-filament::button type="submit" color="success" icon="heroicon-o-check">
                Submit Quote
            </x-filament::button>

            {{-- Was BulkRfqResource::getUrl(...) with no import: inside a Blade view
                 that resolves to the global namespace, so it failed even once the
                 components above were fixed. --}}
            <x-filament::button
                href="{{ \App\Filament\Vendor\Resources\BulkRfqResource::getUrl('view', ['record' => $record]) }}"
                color="gray"
                tag="a"
                class="ml-2">
                Cancel
            </x-filament::button>
        </x-filament::section>
    </x-filament-panels::form>
</x-filament-panels::page>
