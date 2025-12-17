<x-filament::section heading="Low Stock (<= 5)">
    <ul class="space-y-3">
        @foreach ($products as $p)
            <li class="flex justify-between">
                <span>{{ $p->name }}</span>
                <span class="text-red-600 font-bold">{{ $p->quantity }}</span>
            </li>
        @endforeach
    </ul>
</x-filament::section>
