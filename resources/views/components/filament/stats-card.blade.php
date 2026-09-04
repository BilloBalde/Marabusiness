@props([
    'heading' => '',
    'value' => '',
])

<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $heading }}</p>
    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $value }}</p>
</div>
