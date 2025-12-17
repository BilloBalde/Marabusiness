<x-filament-panels::page>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @livewire('vendor.dashboard.vendor-stats')
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        @livewire('vendor.dashboard.latest-orders')
        @livewire('vendor.dashboard.top-products')
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-1 gap-6 mt-6">
        @livewire('vendor.dashboard.low-stock')
    </div>

</x-filament-panels::page>
