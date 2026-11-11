<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        @include('livewire.partials.nav-header', ['tileContent' => 'sourcing.my_quotes', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('sourcing.my_quotes') }}</h1>
            <div class="w-16 h-1 bg-[#D4AF37] mt-4 rounded-full"></div>
        </div>

        @if ($quotes->isEmpty())
            <div class="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="inline-flex w-16 h-16 items-center justify-center rounded-full bg-gray-100 mb-4">
                    <i class="fa-solid fa-file-signature text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-600 text-lg font-medium mb-5">{{ __('sourcing.no_quotes_yet') }}</p>
                <a href="{{ route('sourcing.index') }}"
                   class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-[#D4AF37] rounded-lg shadow-sm hover:bg-[#C9A227] hover:shadow-md transition-all">
                    Découvrir le sourcing
                    <i class="fa-solid fa-arrow-right ml-2"></i>
                </a>
            </div>
        @else
            <div class="space-y-3">
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-700',
                        'processing' => 'bg-blue-100 text-blue-700',
                        'validated' => 'bg-green-100 text-green-700',
                        'cancelled' => 'bg-gray-200 text-gray-600',
                    ];
                @endphp
                @foreach ($quotes as $quote)
                    <a wire:navigate href="{{ route('quotes.show', $quote->id) }}" wire:key="quote-{{ $quote->id }}"
                       class="flex items-center gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:shadow-md hover:border-[#D4AF37]/40 transition-all">
                        <div class="flex-shrink-0 w-14 h-14 rounded-xl bg-[#D4AF37]/10 flex items-center justify-center overflow-hidden">
                            @if (!empty($quote->sourceProduct?->images[0]))
                                <img src="{{ url('uploads/' . $quote->sourceProduct->images[0]) }}" class="w-full h-full object-cover" alt="">
                            @else
                                <i class="fa-solid fa-box text-xl text-[#D4AF37]"></i>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-800 truncate">{{ $quote->sourceProduct?->name_text ?? '—' }}</p>
                            <p class="text-sm text-gray-500">{{ $quote->sourceProduct?->sourcing?->title_text }} · {{ $quote->created_at->format('d/m/Y') }}</p>
                        </div>
                        <span class="flex-shrink-0 text-xs font-medium px-3 py-1 rounded-full {{ $statusColors[$quote->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ __('sourcing.status_' . $quote->status) }}
                        </span>
                        <i class="fa-solid fa-chevron-right text-gray-300"></i>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
