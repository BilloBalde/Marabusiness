<x-filament::section heading="Latest Orders">

    <table class="w-full text-left">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($orders as $o)
                <tr>
                    <td>{{ $o->order_number }}</td>
                    <td>{{ $o->user->name }}</td>
                    <td>{{ number_format($o->grand_total,2) }}</td>
                    <td>{{ ucfirst($o->payment_status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</x-filament::section>
