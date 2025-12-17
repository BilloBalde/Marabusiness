<x-filament::section heading="Top Customers">
    <ul class="space-y-3">
        @foreach ($customers as $c)
            <li class="flex justify-between">
                <span>{{ $c->user->name }}</span>
                <span class="font-bold">{{ number_format($c->spent,2) }}</span>
            </li>
        @endforeach
    </ul>
</x-filament::section>
