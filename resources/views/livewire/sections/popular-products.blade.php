<section class="px-6 py-20 mx-auto text-center max-w-7xl">
    <h2 class="mb-12 text-4xl font-bold">{{ __('ui.popular_categories.title') }}</h2>
    <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 sm:gap-6">
        @foreach ($categories as $category)
        <a class="flex flex-col transition bg-white border shadow-sm group rounded-xl hover:shadow-md dark:bg-slate-900 dark:border-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="/products?selectedCategories[0]={{ $category->id }}" wire:key="{{ $category->id }}">
        <div class="p-4 md:p-5">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <img class="h-[2.375rem] w-[2.375rem] rounded-full" src="{{ url('uploads', $category->image) }}" alt="Image Description">
              <div class="ms-3">
                <h3 class="font-semibold text-gray-800 group-hover:text-blue-600 dark:group-hover:text-gray-400 dark:text-gray-200">
                  {{ $category->name }}
                </h3>
              </div>
            </div>
            <div class="ps-3">
              <svg class="flex-shrink-0 w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6" />
              </svg>
            </div>
          </div>
        </div>
      </a>
        @endforeach
    </div>
    <div class="mt-8">
        <a href="/categories" class="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
            {{ __('ui.popular_categories.view_all') }}
            <svg class="w-4 h-4 ms-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
            </svg>
        </a>
    </div>
</section>
<!-- End of Shop by Brand Section -->
