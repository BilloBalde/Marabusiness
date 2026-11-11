<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @include('livewire.partials.nav-header', ['tileContent' => 'ui.navbar.categories', 'hasSub' => false, 'subContent' => '', 'subLink' => ''])

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Toutes les catégories</h1>
        <p class="text-gray-500 mt-1">Parcourez le catalogue par catégorie.</p>
        <div class="w-16 h-1 bg-[#D4AF37] mt-4 rounded-full"></div>
    </div>

    @if ($categories->isEmpty())
        <div class="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="inline-flex w-16 h-16 items-center justify-center rounded-full bg-gray-100 mb-4">
                <i class="fa-solid fa-shapes text-gray-400 text-2xl"></i>
            </div>
            <p class="text-gray-600 text-lg font-medium">Aucune catégorie pour le moment.</p>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($categories as $category)
                <a href="/products?selectedCategories[0]={{ $category->id }}"
                   wire:key="{{ $category->id }}"
                   class="group flex items-center gap-4 p-5 bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-[#D4AF37]/40 transition-all duration-300">
                    <div class="flex-shrink-0 w-16 h-16 rounded-xl bg-[#D4AF37]/10 flex items-center justify-center overflow-hidden group-hover:bg-[#D4AF37]/15 transition-colors">
                        @if ($category->image)
                            <img class="w-full h-full object-cover" src="{{ url('uploads', $category->image) }}" alt="{{ $category->name }}">
                        @else
                            <i class="fa-solid fa-shapes text-2xl text-[#D4AF37]"></i>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-gray-800 group-hover:text-[#D4AF37] transition-colors truncate">
                            {{ $category->name }}
                        </h3>
                        <span class="text-sm text-gray-400">Voir les produits</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-gray-300 group-hover:text-[#D4AF37] group-hover:translate-x-1 transition-all"></i>
                </a>
            @endforeach
        </div>
    @endif
</div>
