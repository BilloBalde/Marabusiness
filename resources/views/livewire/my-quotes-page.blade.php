<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-4xl mx-auto px-6">
        @include('livewire.partials.nav-header', ['tileContent' => 'sourcing.my_quotes', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])

        <h1 class="text-3xl font-bold text-gray-800 mb-6">{{ __('sourcing.my_quotes') }}</h1>

        @if ($quotes->isEmpty())
            <p class="text-gray-500">{{ __('sourcing.no_quotes_yet') }}</p>
        @else
            <div class="space-y-3">
                @foreach ($quotes as $quote)
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-700',
                            'processing' => 'bg-blue-100 text-blue-700',
                            'validated' => 'bg-green-100 text-green-700',
                            'cancelled' => 'bg-gray-200 text-gray-600',
                        ];
                    @endphp
                    <a wire:navigate href="{{ route('quotes.show', $quote->id) }}"
                       class="flex items-center justify-between bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-shadow">
                        <div>
                            <p class="font-semibold text-gray-800">{{ $quote->sourceProduct?->name_text ?? '—' }}</p>
                            <p class="text-sm text-gray-500">{{ $quote->sourceProduct?->sourcing?->title_text }} · {{ $quote->created_at->format('d/m/Y') }}</p>
                        </div>
                        <span class="text-xs font-medium px-3 py-1 rounded-full {{ $statusColors[$quote->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ __('sourcing.status_' . $quote->status) }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
