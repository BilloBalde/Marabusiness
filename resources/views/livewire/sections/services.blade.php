<section class="py-20 bg-gray-50 dark:bg-gray-900">
  <div class="px-6 mx-auto max-w-7xl">
    <div
      class="flex flex-col gap-8 md:flex-row"
      x-data="{ tab: '{{ $services->first()->slug ?? 'default' }}' }"
    >

      <!-- Sidebar Tabs -->
      <aside class="w-full md:w-1/4">
        <div class="space-y-4">
          @foreach ($services as $service)
            <button
              @click="tab = '{{ $service->slug }}'"
              :class="tab === '{{ $service->slug }}'
                ? 'bg-blue-100 text-blue-800 border-blue-500'
                : 'bg-white text-gray-700 border-gray-300'"
              class="flex items-center w-full px-4 py-3 transition border rounded-lg shadow-sm hover:shadow-md"
            >
              @if ($service->icon)
                <img src="{{ asset('uploads/' . $service->icon) }}" alt="{{ $service->name }}" class="w-6 h-6 mr-3">
              @else
                <div class="w-6 h-6 mr-3 bg-gray-200 rounded"></div>
              @endif
              <span class="font-semibold">{{ $service->name }}</span>
            </button>
          @endforeach
        </div>
      </aside>

      <!-- Tab Content -->
      <div class="w-full p-8 space-y-6 bg-white rounded-lg shadow-lg md:w-3/4 dark:bg-gray-800">
        @foreach ($services as $service)
          <div x-show="tab === '{{ $service->slug }}'" class="p-6 space-y-4 rounded-lg bg-gray-50 dark:bg-gray-900">
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white">{{ $service->name }}</h2>
            <div class="prose text-justify dark:prose-invert max-w-none">
            {!! \Illuminate\Support\Str::markdown($service->description ?? 'Aucune description disponible.') !!}
            </div>
            {{-- Add more dynamic fields here as needed --}}
          </div>
        @endforeach
      </div>

    </div>
  </div>
</section>

<script src="https://unpkg.com/alpinejs" defer></script>
