<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <x-filament::stats-card heading="Today Sales" value="{{ number_format($todaySales,2) }}" />
    <x-filament::stats-card heading="This Month" value="{{ number_format($monthSales,2) }}" />
    <x-filament::stats-card heading="Customers" value="{{ $totalCustomers }}" />
    <x-filament::stats-card heading="Pending Orders" value="{{ $pendingOrders }}" />
</div>