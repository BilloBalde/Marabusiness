<div class="space-y-4">
    @foreach ($reviews as $review)
        <div class="border p-3 rounded-lg">
            <div class="font-semibold">
                {{ $review->user->name }} — {{ $review->rating }} ★
            </div>

            <div class="text-sm text-gray-600">
                {{ $review->created_at->diffForHumans() }}
            </div>

            <div class="mt-2">
                {{ $review->comment }}
            </div>
        </div>
    @endforeach

    @if ($reviews->isEmpty())
        <p class="text-gray-500">No reviews yet for this vendor.</p>
    @endif
</div>
