<x-filament-panels::page>

    <x-filament::section>
        <livewire:dashboard.stats-overview />
    </x-filament::section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <livewire:dashboard.sales-chart />
        <livewire:dashboard.payment-method-chart />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <livewire:dashboard.best-products-chart />
        <livewire:dashboard.top-customers />
    </div>

    <div class="mt-6">
        <livewire:dashboard.latest-orders />
    </div>

    <div class="mt-6">
        <livewire:dashboard.low-stock />
    </div>

</x-filament-panels::page>